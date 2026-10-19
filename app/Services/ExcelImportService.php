<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Catalogo;
use App\Models\Respuesta;
use DateTime;
use Exception;
use InvalidArgumentException;
use PDO;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Filtro personalizado para leer hojas de cálculo por bloques (chunks) de filas.
 */
class ChunkReadFilter implements IReadFilter
{
    private int $startRow = 0;
    private int $endRow = 0;

    /**
     * Establece el rango de filas a leer.
     */
    public function setRows(int $startRow, int $chunkSize): void
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize - 1;
    }

    /**
     * Determina si la celda debe ser leída por el lector de PhpSpreadsheet.
     */
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return $row >= $this->startRow && $row <= $this->endRow;
    }
}

/**
 * Servicio de importación masiva de encuestas de evaluación directiva desde archivos Excel/CSV.
 */
class ExcelImportService
{
    private const CHUNK_SIZE = 1000;

    /**
     * Caché en memoria para evitar consultas repetitivas a la base de datos.
     */
    private array $plantelCache = [];
    private array $licenciaturaCache = [];
    private array $directorCache = [];

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Importa las respuestas de evaluación desde un archivo Excel/CSV para un periodo específico.
     *
     * @param string $filePath Ruta absoluta o relativa al archivo Excel/CSV.
     * @param string $periodoId Identificador del periodo (e.g. '2026-3').
     * @return array Resumen del proceso ['total_read' => int, 'inserted' => int, 'skipped' => int, 'errors' => array]
     * @throws Exception Si ocurre un error irrecuperable en la lectura o transacción.
     */
    public function import(string $filePath, string $periodoId): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new InvalidArgumentException("El archivo '{$filePath}' no existe o no tiene permisos de lectura.");
        }

        $totalRead = 0;
        $inserted = 0;
        $skipped = 0;
        $errors = [];

        // Limpiar cachés para una importación limpia
        $this->plantelCache = [];
        $this->licenciaturaCache = [];
        $this->directorCache = [];

        $this->pdo->beginTransaction();

        try {
            // 1. Limpieza previa del periodo (reimportación limpia)
            if (class_exists(Respuesta::class) && method_exists(Respuesta::class, 'deleteByPeriodo')) {
                Respuesta::deleteByPeriodo($this->pdo, $periodoId);
            } else {
                $stmtDel = $this->pdo->prepare("DELETE FROM respuestas_evaluacion WHERE id_periodo = :periodo");
                $stmtDel->execute(['periodo' => $periodoId]);
            }

            // 2. Configurar el lector de PhpSpreadsheet con detección automática de tipo
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);

            // Obtener el número total de filas del archivo
            $totalRows = 0;
            try {
                $info = $reader->listWorksheetInfo($filePath);
                $totalRows = isset($info[0]['totalRows']) ? (int)$info[0]['totalRows'] : 0;
            } catch (\Throwable $e) {
                // Si listWorksheetInfo no está soportado por el lector, cargamos dimensiones básicas
                $tempSpreadsheet = $reader->load($filePath);
                $totalRows = $tempSpreadsheet->getActiveSheet()->getHighestRow();
                $tempSpreadsheet->disconnectWorksheets();
                unset($tempSpreadsheet);
            }

            if ($totalRows === 0) {
                $this->pdo->commit();
                return [
                    'total_read' => 0,
                    'inserted' => 0,
                    'skipped' => 0,
                    'errors' => ['El archivo no contiene filas o está vacío.'],
                ];
            }

            // 3. Procesamiento por bloques (chunks) de 1000 filas
            $chunkFilter = new ChunkReadFilter();
            $reader->setReadFilter($chunkFilter);

            for ($startRow = 1; $startRow <= $totalRows; $startRow += self::CHUNK_SIZE) {
                $chunkFilter->setRows($startRow, self::CHUNK_SIZE);
                $spreadsheet = $reader->load($filePath);
                $sheet = $spreadsheet->getActiveSheet();

                $endRow = min($startRow + self::CHUNK_SIZE - 1, $totalRows);
                $batchRows = [];

                // Leer el rango del chunk A{startRow}:N{endRow}
                // rangeToArray(range, nullValue, calculateFormulas, formatData, returnCellRef)
                $chunkData = $sheet->rangeToArray("A{$startRow}:N{$endRow}", null, false, false, false);

                foreach ($chunkData as $offset => $rowValues) {
                    $rowNumber = $startRow + $offset;

                    // Ignorar filas totalmente vacías
                    if ($this->isRowEmpty($rowValues)) {
                        continue;
                    }

                    // Ignorar encabezados si la primera fila corresponde a nombres de columnas
                    if ($rowNumber === 1 && $this->isHeaderRow($rowValues)) {
                        continue;
                    }

                    $totalRead++;

                    // Validar y mapear campos de la fila
                    $rowErrors = [];

                    // Col 0: marca_temporal
                    $marcaTemporal = $this->parseDateTime($rowValues[0] ?? null);
                    if ($marcaTemporal === null) {
                        $marcaTemporal = date('Y-m-d H:i:s');
                    }

                    // Col 1: Plantel
                    $plantelNombre = trim((string)($rowValues[1] ?? ''));
                    if ($plantelNombre === '') {
                        $rowErrors[] = "Fila {$rowNumber}: El nombre del plantel está vacío.";
                        $plantelId = null;
                    } else {
                        $plantelId = $this->getPlantelId($plantelNombre);
                        if ($plantelId === null) {
                            $rowErrors[] = "Fila {$rowNumber}: Plantel '{$plantelNombre}' no encontrado en el catálogo.";
                        }
                    }

                    // Col 2: Grupo y extracción de turno del alumno
                    $grupo = trim((string)($rowValues[2] ?? ''));
                    $studentTurn = $this->extractTurno($grupo);
                    if ($studentTurn === null) {
                        $rowErrors[] = "Fila {$rowNumber}: Formato de grupo inválido ('{$grupo}'). No se pudo determinar el turno.";
                    }

                    // Col 3: Licenciatura
                    $licenciaturaNombre = trim((string)($rowValues[3] ?? ''));
                    if ($licenciaturaNombre === '') {
                        $rowErrors[] = "Fila {$rowNumber}: El nombre de la licenciatura está vacío.";
                        $licenciaturaId = null;
                    } else {
                        $licenciaturaId = $this->getLicenciaturaId($licenciaturaNombre);
                        if ($licenciaturaId === null) {
                            $rowErrors[] = "Fila {$rowNumber}: Licenciatura '{$licenciaturaNombre}' no encontrada en el catálogo.";
                        }
                    }

                    // Preguntas numéricas (5-10) y Sí/No (5 o 10)
                    $p1 = $this->validateNumericScore($rowValues[4] ?? null, 'p1_horario_cubiculo', $rowNumber, $rowErrors);
                    $p2 = $this->validateNumericScore($rowValues[5] ?? null, 'p2_atencion_respetuosa', $rowNumber, $rowErrors);
                    $p3 = $this->validateNumericScore($rowValues[6] ?? null, 'p3_reinscripcion_personal', $rowNumber, $rowErrors);
                    $p4 = $this->validateSiNoScore($rowValues[7] ?? null, 'p4_semblanza', $rowNumber, $rowErrors);
                    $p5 = $this->validateSiNoScore($rowValues[8] ?? null, 'p5_actividad_extracurricular', $rowNumber, $rowErrors);
                    $p6 = $this->validateSiNoScore($rowValues[9] ?? null, 'p6_maestros_activos', $rowNumber, $rowErrors);
                    $p7 = $this->validateNumericScore($rowValues[10] ?? null, 'p7_acciones_disciplinarias', $rowNumber, $rowErrors);
                    $p8 = $this->validateNumericScore($rowValues[11] ?? null, 'p8_observaciones_maestros', $rowNumber, $rowErrors);
                    $p9 = $this->validateNumericScore($rowValues[12] ?? null, 'p9_denota_liderazgo', $rowNumber, $rowErrors);
                    $p10 = $this->validateNumericScore($rowValues[13] ?? null, 'p10_evaluacion_general', $rowNumber, $rowErrors);

                    // Si hubo algún error en la fila, se descarta y se registra el motivo
                    if (!empty($rowErrors)) {
                        foreach ($rowErrors as $err) {
                            $errors[] = $err;
                        }
                        $skipped++;
                        continue;
                    }

                    // Asignación de Director según el turno del alumno
                    // Turno estudiante M o D -> Director 'M' (Matutino)
                    // Turno estudiante V o N -> Director 'T' (Tarde / Vespertino)
                    $directorTurno = ($studentTurn === 'M' || $studentTurn === 'D') ? 'M' : 'T';
                    $directorId = $this->getDirectorId($plantelId, $licenciaturaId, $directorTurno);

                    $batchRows[] = [
                        'id_periodo' => $periodoId,
                        'id_plantel' => $plantelId,
                        'id_licenciatura' => $licenciaturaId,
                        'id_director' => $directorId,
                        'grupo' => $grupo,
                        'turno' => $studentTurn,
                        'marca_temporal' => $marcaTemporal,
                        'p1_horario_cubiculo' => $p1,
                        'p2_atencion_respetuosa' => $p2,
                        'p3_reinscripcion_personal' => $p3,
                        'p4_semblanza' => $p4,
                        'p5_actividad_extracurricular' => $p5,
                        'p6_maestros_activos' => $p6,
                        'p7_acciones_disciplinarias' => $p7,
                        'p8_observaciones_maestros' => $p8,
                        'p9_denota_liderazgo' => $p9,
                        'p10_evaluacion_general' => $p10,
                    ];
                }

                // Insertar lote del chunk actual si contiene filas válidas
                if (!empty($batchRows)) {
                    if (class_exists(Respuesta::class) && method_exists(Respuesta::class, 'insertBatch')) {
                        $inserted += Respuesta::insertBatch($this->pdo, $batchRows);
                    } else {
                        $inserted += $this->directInsertBatch($batchRows);
                    }
                }

                // Liberar memoria del chunk en PhpSpreadsheet
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
                gc_collect_cycles();
            }

            $this->pdo->commit();

            return [
                'total_read' => $totalRead,
                'inserted' => $inserted,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new Exception("Error crítico durante la importación: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Extrae el turno estudiantil a partir del código del grupo.
     * Patrón: Antes del guion '-', el penúltimo carácter determina el turno:
     * - '0' -> 'M' (Matutino)
     * - '1' -> 'D' (Diurno)
     * - '2' -> 'V' (Vespertino)
     * - '3' -> 'N' (Nocturno)
     *
     * @param string $grupo Código de grupo (e.g. 'LDE0201-1').
     * @return string|null 'M', 'D', 'V', 'N' o null si el formato no es válido.
     */
    public function extractTurno(string $grupo): ?string
    {
        $grupo = trim($grupo);
        if ($grupo === '') {
            return null;
        }

        $prefix = str_contains($grupo, '-') ? explode('-', $grupo)[0] : $grupo;
        $prefix = trim($prefix);

        if (strlen($prefix) < 2) {
            return null;
        }

        $penultimateChar = substr($prefix, -2, 1);

        return match ($penultimateChar) {
            '0' => 'M',
            '1' => 'D',
            '2' => 'V',
            '3' => 'N',
            default => null,
        };
    }

    /**
     * Convierte valores de texto o booleanos de tipo Sí/No a escala 10 o 5.
     *
     * @param mixed $value Valor a convertir.
     * @return int|null 10 para afirmativo, 5 para negativo, o null si es nulo o inválido.
     */
    public function convertSiNo(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            $num = (int)$value;
            if ($num === 10 || $num === 5) {
                return $num;
            }
            if ($num >= 8) {
                return 10;
            }
            if ($num <= 5 && $num > 0) {
                return 5;
            }
            return null;
        }

        $str = mb_strtolower(trim((string)$value), 'UTF-8');
        if ($str === '') {
            return null;
        }

        // Normalizar acentos
        $str = str_replace(['í', 'ì'], 'i', $str);

        if (in_array($str, ['si', 'yes', 'true', 's', 'y', '1', '10'], true)) {
            return 10;
        }

        if (in_array($str, ['no', 'false', 'n', '0', '5'], true)) {
            return 5;
        }

        return null;
    }

    /**
     * Parsea valores de fecha y hora provenientes de Excel (tanto serial como string).
     */
    private function parseDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTime) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float)$value);
                return $dt->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // Continuar a parser de string
            }
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }

            $formats = [
                'd/m/Y H:i:s',
                'd/m/Y H:i',
                'd/m/Y',
                'Y-m-d H:i:s',
                'Y-m-d H:i',
                'Y-m-d',
                'm/d/Y H:i:s',
                'm/d/Y H:i',
                'm/d/Y',
            ];

            foreach ($formats as $fmt) {
                $dt = DateTime::createFromFormat($fmt, $trimmed);
                if ($dt !== false) {
                    return $dt->format('Y-m-d H:i:s');
                }
            }

            try {
                $dt = new DateTime($trimmed);
                return $dt->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Valida y ajusta valores numéricos en el rango 5-10.
     */
    private function validateNumericScore(mixed $value, string $field, int $rowNum, array &$errors): ?int
    {
        if ($value === null || trim((string)$value) === '') {
            $errors[] = "Fila {$rowNum}: Falta el valor para {$field}.";
            return null;
        }

        if (!is_numeric($value)) {
            $errors[] = "Fila {$rowNum}: El valor para {$field} ('{$value}') no es numérico.";
            return null;
        }

        $score = (int)round((float)$value);

        // Clamping seguro entre 5 y 10
        if ($score < 5) {
            $score = 5;
        } elseif ($score > 10) {
            $score = 10;
        }

        return $score;
    }

    /**
     * Valida valores de tipo Sí/No para preguntas que admiten 5 o 10.
     */
    private function validateSiNoScore(mixed $value, string $field, int $rowNum, array &$errors): ?int
    {
        $converted = $this->convertSiNo($value);
        if ($converted === null) {
            $errors[] = "Fila {$rowNum}: El valor para {$field} ('{$value}') no es válido (se esperaba 'Sí' o 'No').";
            return null;
        }

        return $converted;
    }

    /**
     * Detecta si una fila corresponde a encabezados de columna.
     */
    private function isHeaderRow(array $row): bool
    {
        $col0 = mb_strtolower(trim((string)($row[0] ?? '')));
        $col1 = mb_strtolower(trim((string)($row[1] ?? '')));
        $col3 = mb_strtolower(trim((string)($row[3] ?? '')));
        $col4 = trim((string)($row[4] ?? ''));

        if (str_contains($col0, 'marca') || str_contains($col0, 'fecha') || str_contains($col0, 'timestamp') || str_contains($col0, 'hora')) {
            return true;
        }

        if (str_contains($col1, 'plantel') || str_contains($col1, 'campus')) {
            return true;
        }

        if (str_contains($col3, 'licenciatura') || str_contains($col3, 'carrera')) {
            return true;
        }

        if (!empty($col4) && !is_numeric($col4)) {
            return true;
        }

        return false;
    }

    /**
     * Comprueba si una fila está completamente vacía.
     */
    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string)$cell) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtiene el ID del plantel mediante el modelo Catalogo o consulta directa con caché.
     */
    private function getPlantelId(string $nombre): ?int
    {
        $key = mb_strtolower(trim($nombre));
        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, $this->plantelCache)) {
            return $this->plantelCache[$key];
        }

        $id = null;
        if (class_exists(Catalogo::class) && method_exists(Catalogo::class, 'findPlantelByName')) {
            $id = Catalogo::findPlantelByName($this->pdo, $nombre);
        } else {
            $stmt = $this->pdo->prepare("SELECT id_plantel FROM planteles WHERE LOWER(TRIM(nombre)) = LOWER(:nombre) LIMIT 1");
            $stmt->execute(['nombre' => trim($nombre)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $id = $row ? (int)$row['id_plantel'] : null;
        }

        $this->plantelCache[$key] = $id;
        return $id;
    }

    /**
     * Obtiene el ID de la licenciatura mediante el modelo Catalogo o consulta directa con caché.
     */
    private function getLicenciaturaId(string $nombre): ?int
    {
        $key = mb_strtolower(trim($nombre));
        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, $this->licenciaturaCache)) {
            return $this->licenciaturaCache[$key];
        }

        $id = null;
        if (class_exists(Catalogo::class) && method_exists(Catalogo::class, 'findLicenciaturaByName')) {
            $id = Catalogo::findLicenciaturaByName($this->pdo, $nombre);
        } else {
            $stmt = $this->pdo->prepare("SELECT id_licenciatura FROM licenciaturas WHERE LOWER(TRIM(nombre)) = LOWER(:nombre) LIMIT 1");
            $stmt->execute(['nombre' => trim($nombre)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $id = $row ? (int)$row['id_licenciatura'] : null;
        }

        $this->licenciaturaCache[$key] = $id;
        return $id;
    }

    /**
     * Obtiene el ID del director a partir del plantel, licenciatura y turno consolidado ('M' o 'T').
     */
    private function getDirectorId(int $plantelId, int $licenciaturaId, string $turnoConsolidado): ?int
    {
        $cacheKey = "{$plantelId}_{$licenciaturaId}_{$turnoConsolidado}";
        if (array_key_exists($cacheKey, $this->directorCache)) {
            return $this->directorCache[$cacheKey];
        }

        $id = null;
        if (class_exists(Catalogo::class) && method_exists(Catalogo::class, 'findDirector')) {
            $id = Catalogo::findDirector($this->pdo, $plantelId, $licenciaturaId, $turnoConsolidado);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT id_director FROM directores 
                 WHERE id_plantel = :plantel 
                   AND id_licenciatura = :licenciatura 
                   AND turno = :turno 
                   AND estatus = 'ACTIVO' 
                 LIMIT 1"
            );
            $stmt->execute([
                'plantel' => $plantelId,
                'licenciatura' => $licenciaturaId,
                'turno' => $turnoConsolidado,
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $id = $row ? (int)$row['id_director'] : null;
        }

        $this->directorCache[$cacheKey] = $id;
        return $id;
    }

    /**
     * Inserción por lotes directa como fallback si Respuesta::insertBatch no está disponible.
     */
    private function directInsertBatch(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $columns = [
            'id_periodo', 'id_plantel', 'id_licenciatura', 'id_director',
            'grupo', 'turno', 'marca_temporal',
            'p1_horario_cubiculo', 'p2_atencion_respetuosa', 'p3_reinscripcion_personal',
            'p4_semblanza', 'p5_actividad_extracurricular', 'p6_maestros_activos',
            'p7_acciones_disciplinarias', 'p8_observaciones_maestros',
            'p9_denota_liderazgo', 'p10_evaluacion_general',
        ];

        $colSql = implode(', ', $columns);
        $rowPlaceholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        // Dividir en bloques de 200 para evitar límites de parámetros en MySQL
        $subBatches = array_chunk($rows, 200);
        $totalInserted = 0;

        foreach ($subBatches as $subBatch) {
            $placeholders = implode(', ', array_fill(0, count($subBatch), $rowPlaceholder));
            $sql = "INSERT INTO respuestas_evaluacion ({$colSql}) VALUES {$placeholders}";
            $stmt = $this->pdo->prepare($sql);

            $params = [];
            foreach ($subBatch as $row) {
                foreach ($columns as $col) {
                    $params[] = $row[$col] ?? null;
                }
            }

            $stmt->execute($params);
            $totalInserted += count($subBatch);
        }

        return $totalInserted;
    }
}
