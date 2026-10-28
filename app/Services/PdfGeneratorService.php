<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use PDO;

// Requerir FPDF si no está cargado
if (!class_exists('\FPDF')) {
    $fpdfPath = defined('APP_ROOT') ? APP_ROOT . '/../public_html/evaluaciondirectiva/fpdf/fpdf.php' : __DIR__ . '/../../../public_html/evaluaciondirectiva/fpdf/fpdf.php';
    if (file_exists($fpdfPath)) {
        require_once $fpdfPath;
    }
}

/**
 * Servicio generador de reportes PDF oficiales para la Universidad de León (UDL).
 * Ajustado fielmente a los diseños de referencia institucionales en tamaño CARTA (Letter: 215.9 mm x 279.4 mm).
 */
class PdfGeneratorService
{
    private PDO $pdo;
    private array $preguntas;
    private string $logoPath;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        // Cargar descripciones de preguntas configurables
        $preguntasFile = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2)) . '/config/preguntas.php';
        if (file_exists($preguntasFile)) {
            $this->preguntas = require $preguntasFile;
        } else {
            $this->preguntas = [
                'p1' => 'Presencia dentro del horario de atención publicado',
                'p2' => 'Atención oportuna y respetuosa',
                'p3' => 'Realiza la re-inscripción personalmente',
                'p4' => 'Presenta la Semblanza de éxito de cada uno de los maestros',
                'p5' => 'Organizó actividad extracurricular: Congreso, Foro, Panel, Viaje de prácticas, etc.',
                'p6' => 'Los maestros que seleccionó, se encuentran activos en el ámbito profesional',
                'p7' => 'Toma acciones disciplinarias cuando se deteriora el ambiente de empatía y respeto en el grupo',
                'p8' => 'Atiende oportunamente observaciones sobre los maestros en relación a: Faltas, retardos, cambio de parámetros de evaluación, faltas de respeto, actitud inadecuada o prepotente',
                'p9' => 'Mi Director de Facultad denota liderazgo',
                'p10' => 'La evaluación general que otorgo al trabajo de mi Director de Facultad es:'
            ];
        }

        // Ruta del logo institucional
        $publicDir = defined('APP_ROOT') ? APP_ROOT . '/../public_html/evaluaciondirectiva' : dirname(__DIR__, 3) . '/public_html/evaluaciondirectiva';
        $this->logoPath = $publicDir . '/assets/img/UDL_LogotipoColor.png';
    }

    /**
     * Convierte texto a codificación ISO-8859-1 (Windows-1252) para compatibilidad nativa con FPDF.
     */
    private function utf8ToIso(string $text): string
    {
        return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text) ?: $text;
    }

    /**
     * TIPO 1: Reporte General por Región (Orientación Vertical - Letter Portrait)
     * Réplica exacta del formato de la Imagen 1:
     * - Logo institucional UDL centrado o alineado.
     * - Título: Evaluación Directiva [PERIODO]
     * - Subtítulo: Resultados Generales Por Director de Facultad:
     * - Región: REGIÓN [NOMBRE/NÚMERO]
     * - Tabla de 4 columnas: PLANTEL | CARRERA | DIRECTOR DE FACULTAD | PG
     * - Encabezado verde #007547 con texto blanco centrado.
     * - Filas con bordes limpios y número de promedio global resaltado.
     * - Al pie: "Promedio General Por Pregunta:" y "Desviación Estándar:"
     */
    public function generateRegionReport(string $periodoId, ?int $regionId = null): void
    {
        $sql = "SELECT 
                    res.*,
                    d.nombre AS director_nombre,
                    d.apellidos AS director_apellidos,
                    p.nombre AS plantel_nombre,
                    p.id_region,
                    reg.nombre AS region_nombre,
                    l.nombre AS licenciatura_nombre,
                    l.nomenclatura AS licenciatura_nomenclatura
                FROM resultados_evaluacion res
                LEFT JOIN directores d ON res.id_director = d.id_director
                LEFT JOIN planteles p ON res.id_plantel = p.id_plantel
                LEFT JOIN licenciaturas l ON res.id_licenciatura = l.id_licenciatura
                LEFT JOIN regiones reg ON p.id_region = reg.id_region
                WHERE res.id_periodo = :id_periodo";

        $params = [':id_periodo' => $periodoId];
        if ($regionId !== null && $regionId > 0) {
            $sql .= " AND p.id_region = :id_region";
            $params[':id_region'] = $regionId;
        }
        $sql .= " ORDER BY res.promedio_global DESC, p.nombre ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Nombre de la región
        $nombreRegion = 'TODAS LAS REGIONES';
        if ($regionId !== null && !empty($resultados)) {
            $nombreRegion = strtoupper((string)($resultados[0]['region_nombre'] ?? "REGIÓN {$regionId}"));
        } elseif ($regionId !== null) {
            $regStmt = $this->pdo->prepare("SELECT nombre FROM regiones WHERE id_region = ?");
            $regStmt->execute([$regionId]);
            $rNom = $regStmt->fetchColumn();
            if ($rNom) {
                $nombreRegion = strtoupper((string)$rNom);
            }
        }

        $pdf = new \FPDF('P', 'mm', 'Letter'); // Letter: 215.9 x 279.4 mm
        $pdf->SetMargins(14, 12, 14);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage();

        // 1. Logo UDL
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, 14, 10, 34);
        }

        // 2. Encabezado institucional
        $pdf->SetY(12);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(15, 57, 43); // UDL Dark
        $pdf->Cell(0, 6, $this->utf8ToIso("Evaluación Directiva {$periodoId}"), 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(0, 117, 71); // UDL Green
        $pdf->Cell(0, 5, $this->utf8ToIso("Resultados Generales Por Director de Facultad:"), 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(15, 23, 42); // Slate 900
        $pdf->Cell(0, 6, $this->utf8ToIso($nombreRegion), 0, 1, 'C');
        $pdf->Ln(5);

        // 3. Tabla de Resultados
        // Ancho disponible: 215.9 - 28 = 187.9 mm
        // Plantel: 42mm | Carrera: 30mm | Director: 95mm | PG: 20mm = 187mm
        $wPlantel = 42;
        $wCarrera = 30;
        $wDirector = 95;
        $wPG = 20;

        $drawHeader = function() use ($pdf, $wPlantel, $wCarrera, $wDirector, $wPG) {
            $pdf->SetFillColor(0, 117, 71); // #007547
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetDrawColor(0, 90, 54);
            $pdf->SetLineWidth(0.3);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell($wPlantel, 7, $this->utf8ToIso('PLANTEL'), 1, 0, 'C', true);
            $pdf->Cell($wCarrera, 7, $this->utf8ToIso('CARRERA'), 1, 0, 'C', true);
            $pdf->Cell($wDirector, 7, $this->utf8ToIso('DIRECTOR DE FACULTAD'), 1, 0, 'C', true);
            $pdf->Cell($wPG, 7, $this->utf8ToIso('PG'), 1, 1, 'C', true);
        };

        $drawHeader();

        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetDrawColor(203, 213, 225); // Slate 300
        $pdf->SetFont('Arial', '', 8);

        $sumaPG = 0.0;
        $count = count($resultados);
        $pgValues = [];
        $fill = false;

        foreach ($resultados as $row) {
            $dirNombre = trim(($row['director_nombre'] ?? '') . ' ' . ($row['director_apellidos'] ?? ''));
            if ($dirNombre === '') {
                $dirNombre = 'SIN ASIGNAR';
            }
            $plantel = (string)($row['plantel_nombre'] ?? 'N/D');
            $carreraNom = (string)($row['licenciatura_nomenclatura'] ?? $row['licenciatura_nombre'] ?? 'N/D');
            $pg = (float)($row['promedio_global'] ?? 0);
            $sumaPG += $pg;
            $pgValues[] = $pg;

            // Salto de página preventivo
            if ($pdf->GetY() > 252) {
                $pdf->AddPage();
                $drawHeader();
                $pdf->SetTextColor(30, 41, 59);
                $pdf->SetDrawColor(203, 213, 225);
                $pdf->SetFont('Arial', '', 8);
            }

            if ($fill) {
                $pdf->SetFillColor(246, 248, 247);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }

            $pdf->Cell($wPlantel, 5.5, $this->utf8ToIso($plantel), 'LRB', 0, 'L', $fill);
            $pdf->Cell($wCarrera, 5.5, $this->utf8ToIso($carreraNom), 'LRB', 0, 'C', $fill);
            $pdf->Cell($wDirector, 5.5, $this->utf8ToIso($dirNombre), 'LRB', 0, 'L', $fill);
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->Cell($wPG, 5.5, number_format($pg, 2), 'LRB', 1, 'C', $fill);
            $pdf->SetFont('Arial', '', 8);

            $fill = !$fill;
        }

        // Totales y Desviación Estándar
        $promedioFinal = $count > 0 ? ($sumaPG / $count) : 0.0;
        $desvEst = 0.0;
        if ($count > 1) {
            $sumSq = 0.0;
            foreach ($pgValues as $val) {
                $sumSq += pow($val - $promedioFinal, 2);
            }
            $desvEst = sqrt($sumSq / $count);
        }

        // Pie de tabla fiel a la imagen de referencia
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(234, 243, 238);
        $pdf->SetTextColor(15, 57, 43);
        $pdf->Cell($wPlantel + $wCarrera + $wDirector, 6.5, $this->utf8ToIso('Promedio General Por Pregunta:'), 1, 0, 'R', true);
        $pdf->SetTextColor(0, 117, 71);
        $pdf->Cell($wPG, 6.5, number_format($promedioFinal, 2), 1, 1, 'C', true);

        $pdf->SetTextColor(15, 57, 43);
        $pdf->Cell($wPlantel + $wCarrera + $wDirector, 6.5, $this->utf8ToIso('Desviación Estándar:'), 1, 0, 'R', true);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell($wPG, 6.5, number_format($desvEst, 2), 1, 1, 'C', true);

        $filename = "Reporte_General_Region_" . ($regionId ?? 'Todas') . "_{$periodoId}.pdf";
        Logger::info("Generado PDF de Región: {$filename}", ['periodo' => $periodoId, 'region' => $regionId], 'reports');
        $pdf->Output('I', $filename);
        exit;
    }

    /**
     * TIPO 2: Reporte Detallado por Plantel con Preguntas P1-P10 (Orientación Horizontal - Letter Landscape)
     * Réplica exacta del formato de la Imagen 2:
     * - Logo institucional UDL.
     * - Título: Evaluación Directiva [PERIODO]
     * - Subtítulo: Resultados Generales del Plantel: [PLANTEL]
     * - Sección TEMAS DE EVALUACIÓN DIRECTIVA (P1 a P10 ordenados y legibles).
     * - Tabla detallada: CARRERA | DIRECTOR DE FACULTAD | P1 | P2 | P3 | P4 | P5 | P6 | P7 | P8 | P9 | P10 | PG
     * - Al pie: "Promedio General Por Pregunta:" (con promedio por cada P1..P10 y PG)
     * - Fila final: "Desviación Estándar:" (con desviación estándar por cada columna)
     */
    public function generatePlantelReport(string $periodoId, int $plantelId): void
    {
        $stmtPlantel = $this->pdo->prepare("SELECT nombre FROM planteles WHERE id_plantel = ?");
        $stmtPlantel->execute([$plantelId]);
        $plantelNombre = (string)($stmtPlantel->fetchColumn() ?: "Plantel {$plantelId}");

        $sql = "SELECT 
                    res.*,
                    d.nombre AS director_nombre,
                    d.apellidos AS director_apellidos,
                    l.nombre AS licenciatura_nombre,
                    l.nomenclatura AS licenciatura_nomenclatura
                FROM resultados_evaluacion res
                LEFT JOIN directores d ON res.id_director = d.id_director
                LEFT JOIN licenciaturas l ON res.id_licenciatura = l.id_licenciatura
                WHERE res.id_periodo = :id_periodo AND res.id_plantel = :id_plantel
                ORDER BY res.promedio_global DESC, l.nombre ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_periodo' => $periodoId,
            ':id_plantel' => $plantelId
        ]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Letter Landscape: 279.4 x 215.9 mm
        $pdf = new \FPDF('L', 'mm', 'Letter');
        $pdf->SetMargins(12, 10, 12);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();

        // 1. Logo
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, 12, 8, 30);
        }

        // 2. Encabezados
        $pdf->SetY(10);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetTextColor(15, 57, 43);
        $pdf->Cell(0, 5, $this->utf8ToIso("Evaluación Directiva {$periodoId}"), 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(0, 117, 71);
        $pdf->Cell(0, 6, $this->utf8ToIso("Resultados Generales del Plantel: {$plantelNombre}"), 0, 1, 'C');
        $pdf->Ln(2);

        // 3. SECCIÓN TEMAS DE EVALUACIÓN DIRECTIVA (1 Columna Centrada, exactamente como en la Imagen 2 de Referencia)
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 4.5, $this->utf8ToIso('TEMAS DE EVALUACIÓN DIRECTIVA'), 0, 1, 'C');
        $pdf->Ln(1);

        // Dimensiones del bloque centrado:
        // Ancho de página horizontal = 279.4 mm.
        // Ancho total bloque = 220 mm (Margen izquierdo de centrado = (279.4 - 220) / 2 = 29.7 mm).
        // Columna gris (P1..P10): 22 mm | Columna blanca (descripción): 198 mm.
        $wBloque = 220;
        $xStart = (279.4 - $wBloque) / 2;
        $wColLabel = 22;
        $wColText = $wBloque - $wColLabel;

        $pdf->SetTextColor(15, 23, 42); // Slate 900
        $pdf->SetDrawColor(255, 255, 255); // Sin bordes molestos

        for ($i = 1; $i <= 10; $i++) {
            $k = "p{$i}";
            $label = "P{$i}";
            $desc = $this->preguntas[$k] ?? '';

            // Pregunta P8 tiene texto más largo; usamos una altura y tamaño adecuados
            $isLong = ($i === 8);
            $fontSize = $isLong ? 5.8 : 6.5;
            $rowHeight = $isLong ? 4.0 : 3.4;

            $pdf->SetX($xStart);

            // Celda de etiqueta con fondo gris suave #E2E8F0
            $pdf->SetFillColor(226, 232, 240);
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->Cell($wColLabel, $rowHeight, $label, 0, 0, 'C', true);

            // Celda de texto descriptivo con fondo blanco
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetFont('Arial', '', $fontSize);
            $pdf->Cell($wColText, $rowHeight, '  ' . $this->utf8ToIso($desc), 0, 1, 'L', false);
        }

        $pdf->Ln(3);

        // 4. TABLA DE RESULTADOS
        // Ancho total imprimible: 279.4 - 24 = 255.4 mm
        // Carrera: 25mm | Director: 80mm | P1..P10: 10 x 13mm = 130mm | PG: 20mm = 255mm
        $wCarrera = 25;
        $wDirector = 80;
        $wP = 13;
        $wPG = 20;

        $drawPlantelHeader = function() use ($pdf, $wCarrera, $wDirector, $wP, $wPG) {
            $pdf->SetFillColor(0, 117, 71);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetDrawColor(0, 90, 54);
            $pdf->SetLineWidth(0.3);
            $pdf->SetFont('Arial', 'B', 8);

            $pdf->Cell($wCarrera, 6.5, $this->utf8ToIso('CARRERA'), 1, 0, 'C', true);
            $pdf->Cell($wDirector, 6.5, $this->utf8ToIso('DIRECTOR DE FACULTAD'), 1, 0, 'C', true);
            for ($p = 1; $p <= 10; $p++) {
                $pdf->Cell($wP, 6.5, "P{$p}", 1, 0, 'C', true);
            }
            $pdf->Cell($wPG, 6.5, 'PG', 1, 1, 'C', true);
        };

        $drawPlantelHeader();

        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFont('Arial', '', 7.5);

        $pSums = array_fill(1, 10, 0.0);
        $pArrays = array_fill(1, 10, []);
        $pgSum = 0.0;
        $pgArray = [];
        $count = count($resultados);
        $fill = false;

        foreach ($resultados as $row) {
            $dirNombre = trim(($row['director_nombre'] ?? '') . ' ' . ($row['director_apellidos'] ?? ''));
            if ($dirNombre === '') {
                $dirNombre = 'SIN ASIGNAR';
            }
            $carreraNom = (string)($row['licenciatura_nomenclatura'] ?? $row['licenciatura_nombre'] ?? 'N/D');
            $pg = (float)($row['promedio_global'] ?? 0);
            $pgSum += $pg;
            $pgArray[] = $pg;

            if ($pdf->GetY() > 185) {
                $pdf->AddPage();
                $drawPlantelHeader();
                $pdf->SetTextColor(30, 41, 59);
                $pdf->SetDrawColor(203, 213, 225);
                $pdf->SetFont('Arial', '', 7.5);
            }

            if ($fill) {
                $pdf->SetFillColor(246, 248, 247);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }

            $pdf->Cell($wCarrera, 5, $this->utf8ToIso($carreraNom), 'LRB', 0, 'C', $fill);
            $pdf->Cell($wDirector, 5, $this->utf8ToIso($dirNombre), 'LRB', 0, 'L', $fill);

            for ($p = 1; $p <= 10; $p++) {
                $val = (float)($row["p{$p}_prom"] ?? 0);
                $pSums[$p] += $val;
                $pArrays[$p][] = $val;
                $pdf->Cell($wP, 5, number_format($val, 1), 'LRB', 0, 'C', $fill);
            }

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell($wPG, 5, number_format($pg, 2), 'LRB', 1, 'C', $fill);
            $pdf->SetFont('Arial', '', 7.5);

            $fill = !$fill;
        }

        // Fila 1: Promedio General Por Pregunta
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetFillColor(234, 243, 238);
        $pdf->SetTextColor(15, 57, 43);
        $pdf->Cell($wCarrera + $wDirector, 6, $this->utf8ToIso('Promedio General Por Pregunta:'), 1, 0, 'R', true);

        $pAverages = [];
        for ($p = 1; $p <= 10; $p++) {
            $avg = $count > 0 ? ($pSums[$p] / $count) : 0;
            $pAverages[$p] = $avg;
            $pdf->Cell($wP, 6, number_format($avg, 1), 1, 0, 'C', true);
        }
        $pgAvg = $count > 0 ? ($pgSum / $count) : 0;
        $pdf->SetTextColor(0, 117, 71);
        $pdf->Cell($wPG, 6, number_format($pgAvg, 2), 1, 1, 'C', true);

        // Fila 2: Desviación Estándar
        $pdf->SetTextColor(15, 57, 43);
        $pdf->Cell($wCarrera + $wDirector, 6, $this->utf8ToIso('Desviación Estándar:'), 1, 0, 'R', true);
        $pdf->SetTextColor(71, 85, 105);

        for ($p = 1; $p <= 10; $p++) {
            $std = 0.0;
            if ($count > 1) {
                $sq = 0.0;
                foreach ($pArrays[$p] as $v) {
                    $sq += pow($v - $pAverages[$p], 2);
                }
                $std = sqrt($sq / $count);
            }
            $pdf->Cell($wP, 6, number_format($std, 1), 1, 0, 'C', true);
        }

        $pgStd = 0.0;
        if ($count > 1) {
            $sq = 0.0;
            foreach ($pgArray as $v) {
                $sq += pow($v - $pgAvg, 2);
            }
            $pgStd = sqrt($sq / $count);
        }
        $pdf->Cell($wPG, 6, number_format($pgStd, 2), 1, 1, 'C', true);

        $safePlantel = preg_replace('/[^a-zA-Z0-9_-]/', '_', $plantelNombre);
        $filename = "Reporte_Plantel_{$safePlantel}_{$periodoId}.pdf";
        Logger::info("Generado PDF de Plantel: {$filename}", ['periodo' => $periodoId, 'plantel' => $plantelId], 'reports');
        $pdf->Output('I', $filename);
        exit;
    }

    /**
     * TIPO 3: Reporte de Comparativa Histórica entre Periodos (Orientación Vertical - Letter Portrait)
     * Compara los promedios de cada director entre Periodo 1 y Periodo 2, mostrando variación y tendencia.
     */
    public function generateComparativaReport(string $periodo1, string $periodo2): void
    {
        $statsService = new StatisticsService($this->pdo);
        $comparativa = $statsService->comparePeriodos($periodo1, $periodo2);
        $items = $comparativa['comparativa'] ?? [];

        $pdf = new \FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(14, 12, 14);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage();

        // 1. Logo
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, 14, 10, 32);
        }

        // 2. Encabezado institucional
        $pdf->SetY(12);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(15, 57, 43);
        $pdf->Cell(0, 6, $this->utf8ToIso("Universidad de León"), 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(0, 117, 71);
        $pdf->Cell(0, 5, $this->utf8ToIso("Comparativa Histórica de Evaluación Directiva"), 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(0, 5, $this->utf8ToIso("Ciclo Base: {$periodo1}  vs  Ciclo Comparado: {$periodo2}"), 0, 1, 'C');
        $pdf->Ln(4);

        // 3. Tabla de Comparativa
        // Ancho disponible: 187.9 mm
        // Director: 62mm | Plantel: 40mm | Carrera: 30mm | P1: 18mm | P2: 18mm | Dif: 19mm = 187mm
        $wDir = 62;
        $wPl = 40;
        $wLic = 30;
        $wP1 = 18;
        $wP2 = 18;
        $wDif = 19;

        $drawCompHeader = function() use ($pdf, $wDir, $wPl, $wLic, $wP1, $wP2, $wDif, $periodo1, $periodo2) {
            $pdf->SetFillColor(0, 117, 71);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetDrawColor(0, 90, 54);
            $pdf->SetLineWidth(0.3);
            $pdf->SetFont('Arial', 'B', 8);

            $pdf->Cell($wDir, 7, $this->utf8ToIso('DIRECTOR DE FACULTAD'), 1, 0, 'C', true);
            $pdf->Cell($wPl, 7, $this->utf8ToIso('PLANTEL'), 1, 0, 'C', true);
            $pdf->Cell($wLic, 7, $this->utf8ToIso('CARRERA'), 1, 0, 'C', true);
            $pdf->Cell($wP1, 7, $this->utf8ToIso($periodo1), 1, 0, 'C', true);
            $pdf->Cell($wP2, 7, $this->utf8ToIso($periodo2), 1, 0, 'C', true);
            $pdf->Cell($wDif, 7, $this->utf8ToIso('DIF.'), 1, 1, 'C', true);
        };

        $drawCompHeader();

        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFont('Arial', '', 7.5);

        $fill = false;
        foreach ($items as $row) {
            if ($pdf->GetY() > 255) {
                $pdf->AddPage();
                $drawCompHeader();
                $pdf->SetTextColor(30, 41, 59);
                $pdf->SetDrawColor(203, 213, 225);
                $pdf->SetFont('Arial', '', 7.5);
            }

            if ($fill) {
                $pdf->SetFillColor(246, 248, 247);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }

            $diff = (float)($row['diferencia'] ?? ($row['promedio_p2'] - $row['promedio_p1']));
            $diffText = ($diff > 0 ? '+' : '') . number_format($diff, 2);
            $carreraNom = (string)(!empty($row['nomenclatura']) ? $row['nomenclatura'] : ($row['licenciatura'] ?? 'N/A'));

            $pdf->Cell($wDir, 5.5, $this->utf8ToIso((string)($row['director'] ?? 'N/A')), 'LRB', 0, 'L', $fill);
            $pdf->Cell($wPl, 5.5, $this->utf8ToIso((string)($row['plantel'] ?? 'N/A')), 'LRB', 0, 'L', $fill);
            $pdf->Cell($wLic, 5.5, $this->utf8ToIso($carreraNom), 'LRB', 0, 'C', $fill);
            $pdf->Cell($wP1, 5.5, number_format((float)($row['promedio_p1'] ?? 0), 2), 'LRB', 0, 'C', $fill);
            $pdf->Cell($wP2, 5.5, number_format((float)($row['promedio_p2'] ?? 0), 2), 'LRB', 0, 'C', $fill);

            // Resaltar color de diferencia
            $pdf->SetFont('Arial', 'B', 8);
            if ($diff > 0.05) {
                $pdf->SetTextColor(0, 117, 71); // Verde mejora
            } elseif ($diff < -0.05) {
                $pdf->SetTextColor(225, 29, 72); // Rojo descenso
            } else {
                $pdf->SetTextColor(180, 83, 9); // Ámbar estable
            }

            $pdf->Cell($wDif, 5.5, $diffText, 'LRB', 1, 'C', $fill);

            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('Arial', '', 7.5);
            $fill = !$fill;
        }

        $filename = "Comparativa_{$periodo1}_vs_{$periodo2}.pdf";
        Logger::info("Generado PDF de Comparativa: {$filename}", ['periodo1' => $periodo1, 'periodo2' => $periodo2], 'reports');
        $pdf->Output('I', $filename);
        exit;
    }
}
