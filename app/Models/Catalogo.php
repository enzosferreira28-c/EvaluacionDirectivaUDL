<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Modelo para la gestión de catálogos: Regiones, Planteles, Licenciaturas y Directores.
 */
class Catalogo
{
    // ==========================================
    // REGIONES
    // ==========================================

    /**
     * Obtiene todas las regiones registradas.
     *
     * @return array<int, array{id_region: int, nombre: string}>
     */
    public static function getAllRegiones(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT id_region, nombre FROM regiones ORDER BY nombre ASC');
        return $stmt->fetchAll();
    }

    // ==========================================
    // PLANTELES
    // ==========================================

    /**
     * Obtiene todos los planteles junto con el nombre de su región asociada.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAllPlanteles(PDO $pdo): array
    {
        $sql = 'SELECT p.id_plantel, p.id_region, p.nombre, r.nombre AS region_nombre
                FROM planteles p
                LEFT JOIN regiones r ON p.id_region = r.id_region
                ORDER BY p.nombre ASC';
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Busca un plantel por su nombre exacto (insensible a mayúsculas y espacios).
     */
    public static function findPlantelByName(PDO $pdo, string $nombre): ?int
    {
        $clean = trim($nombre);
        if ($clean === '') {
            return null;
        }

        // 1. Coincidencia directa insensible a mayúsculas
        $stmt = $pdo->prepare(
            'SELECT id_plantel FROM planteles 
             WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre)) 
             LIMIT 1'
        );
        $stmt->execute([':nombre' => $clean]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int)$id;
        }

        // 2. Coincidencia normalizando guiones a espacios (ej. "León-Juárez" vs "León Juárez")
        $stmt2 = $pdo->prepare(
            "SELECT id_plantel FROM planteles 
             WHERE REPLACE(LOWER(TRIM(nombre)), '-', ' ') = REPLACE(LOWER(TRIM(:nombre)), '-', ' ')
             LIMIT 1"
        );
        $stmt2->execute([':nombre' => $clean]);
        $id2 = $stmt2->fetchColumn();
        if ($id2 !== false) {
            return (int)$id2;
        }

        return null;
    }

    /**
     * Obtiene los datos de un plantel específico por su ID.
     *
     * @return array<string, mixed>|null
     */
    public static function findPlantelById(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT p.id_plantel, p.id_region, p.nombre, r.nombre AS region_nombre
             FROM planteles p
             LEFT JOIN regiones r ON p.id_region = r.id_region
             WHERE p.id_plantel = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $plantel = $stmt->fetch();

        return $plantel !== false ? $plantel : null;
    }

    // ==========================================
    // LICENCIATURAS
    // ==========================================

    /**
     * Obtiene todas las licenciaturas registradas.
     *
     * @return array<int, array{id_licenciatura: int, nombre: string}>
     */
    public static function getAllLicenciaturas(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT id_licenciatura, nombre, nomenclatura FROM licenciaturas ORDER BY nombre ASC');
        return $stmt->fetchAll();
    }

    /**
     * Busca una licenciatura por su nombre exacto o normalizado.
     */
    public static function findLicenciaturaByName(PDO $pdo, string $nombre): ?int
    {
        $clean = trim($nombre);
        if ($clean === '') {
            return null;
        }

        // 1. Coincidencia directa exacta por nombre o nomenclatura oficial
        $stmt = $pdo->prepare(
            'SELECT id_licenciatura FROM licenciaturas 
             WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre)) 
                OR LOWER(TRIM(COALESCE(nomenclatura, ""))) = LOWER(TRIM(:nomenclatura))
             LIMIT 1'
        );
        $stmt->execute([
            ':nombre'       => $clean,
            ':nomenclatura' => $clean,
        ]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int)$id;
        }

        // 2. Mapeos de alias comunes en las encuestas
        $lower = mb_strtolower($clean, 'UTF-8');
        if ($lower === 'turismo') {
            $stmtAlias = $pdo->prepare('SELECT id_licenciatura FROM licenciaturas WHERE LOWER(TRIM(nombre)) = :alias LIMIT 1');
            $stmtAlias->execute([':alias' => 'turismo de negocios']);
            $idTurismo = $stmtAlias->fetchColumn();
            if ($idTurismo !== false) return (int)$idTurismo;
        }
        if (str_contains($lower, 'electromecanica') || str_contains($lower, 'electromecánica')) {
            $stmtAlias2 = $pdo->prepare('SELECT id_licenciatura FROM licenciaturas WHERE LOWER(TRIM(nombre)) = :alias LIMIT 1');
            $stmtAlias2->execute([':alias' => 'ingeniería electromecánica industrial']);
            $idElectro = $stmtAlias2->fetchColumn();
            if ($idElectro !== false) return (int)$idElectro;
        }

        // 3. Búsqueda por prefijo
        $stmtPrefix = $pdo->prepare(
            'SELECT id_licenciatura FROM licenciaturas 
             WHERE LOWER(nombre) LIKE LOWER(:prefix) 
             LIMIT 1'
        );
        $stmtPrefix->execute([':prefix' => $clean . '%']);
        $idPrefix = $stmtPrefix->fetchColumn();
        if ($idPrefix !== false) {
            return (int)$idPrefix;
        }

        return null;
    }

    // ==========================================
    // DIRECTORES
    // ==========================================

    /**
     * Obtiene todos los directores con los nombres de su plantel y licenciatura.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAllDirectores(PDO $pdo): array
    {
        $sql = 'SELECT 
                    d.id_director,
                    d.nombre,
                    d.apellidos,
                    d.id_licenciatura,
                    d.id_plantel,
                    d.turno,
                    d.estatus,
                    d.creado_en,
                    p.nombre AS plantel_nombre,
                    p.id_region,
                    l.nombre AS licenciatura_nombre,
                    l.nomenclatura AS licenciatura_nomenclatura,
                    r.nombre AS region_nombre
                FROM directores d
                LEFT JOIN planteles p ON d.id_plantel = p.id_plantel
                LEFT JOIN licenciaturas l ON d.id_licenciatura = l.id_licenciatura
                LEFT JOIN regiones r ON p.id_region = r.id_region
                ORDER BY p.nombre ASC, l.nombre ASC, d.apellidos ASC, d.nombre ASC';

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el listado de directores con estatus ACTIVO.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getDirectoresActivos(PDO $pdo): array
    {
        $sql = "SELECT 
                    d.id_director,
                    d.nombre,
                    d.apellidos,
                    d.id_licenciatura,
                    d.id_plantel,
                    d.turno,
                    d.estatus,
                    d.creado_en,
                    p.nombre AS plantel_nombre,
                    p.id_region,
                    l.nombre AS licenciatura_nombre,
                    l.nomenclatura AS licenciatura_nomenclatura,
                    r.nombre AS region_nombre
                FROM directores d
                LEFT JOIN planteles p ON d.id_plantel = p.id_plantel
                LEFT JOIN licenciaturas l ON d.id_licenciatura = l.id_licenciatura
                LEFT JOIN regiones r ON p.id_region = r.id_region
                WHERE d.estatus = 'ACTIVO'
                ORDER BY p.nombre ASC, l.nombre ASC, d.apellidos ASC, d.nombre ASC";

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Busca el ID del director activo correspondiente al plantel, licenciatura y turno consolidado.
     *
    /**
     * Busca el ID del director activo correspondiente al plantel, licenciatura y turno consolidado.
     */
    public static function findDirector(PDO $pdo, int $plantelId, int $licenciaturaId, string $turnoEstudiante): ?int
    {
        $turno = strtoupper(trim($turnoEstudiante));

        // Determinar turnos directos compatibles para el estudiante:
        // Estudiante M o D -> Busca primero M, luego D
        // Estudiante V o N -> Busca primero V, luego N
        $turnosBuscados = match ($turno) {
            'M' => ['M', 'D'],
            'D' => ['D', 'M'],
            'V' => ['V', 'N'],
            'N' => ['N', 'V'],
            default => [$turno],
        };

        // 1. Buscar coincidencia en turnos específicos
        $inPlaceholders = implode(',', array_fill(0, count($turnosBuscados), '?'));
        $sqlTurno = "SELECT id_director FROM directores 
                     WHERE id_plantel = ? 
                       AND id_licenciatura = ? 
                       AND turno IN ($inPlaceholders) 
                       AND estatus = 'ACTIVO' 
                     ORDER BY FIELD(turno, " . implode(',', array_map(fn($t) => "'$t'", $turnosBuscados)) . ") 
                     LIMIT 1";

        $params = array_merge([$plantelId, $licenciaturaId], $turnosBuscados);
        $stmt = $pdo->prepare($sqlTurno);
        $stmt->execute($params);
        $val = $stmt->fetchColumn();
        if ($val !== false) {
            return (int)$val;
        }

        // 2. Si no hay director específico, buscar director asignado a 'T' (Todas)
        $sqlTodas = "SELECT id_director FROM directores 
                     WHERE id_plantel = ? 
                       AND id_licenciatura = ? 
                       AND turno = 'T' 
                       AND estatus = 'ACTIVO' 
                     LIMIT 1";
        $stmtTodas = $pdo->prepare($sqlTodas);
        $stmtTodas->execute([$plantelId, $licenciaturaId]);
        $valTodas = $stmtTodas->fetchColumn();
        if ($valTodas !== false) {
            return (int)$valTodas;
        }

        // 3. Fallback final: Si la carrera tiene director activo en el plantel sin importar el turno, vincularlo
        $sqlFallback = "SELECT id_director FROM directores 
                        WHERE id_plantel = ? 
                          AND id_licenciatura = ? 
                          AND estatus = 'ACTIVO' 
                        LIMIT 1";
        $stmtFb = $pdo->prepare($sqlFallback);
        $stmtFb->execute([$plantelId, $licenciaturaId]);
        $valFb = $stmtFb->fetchColumn();
        if ($valFb !== false) {
            return (int)$valFb;
        }

        return null;
    }

    /**
     * Inserta un nuevo director y retorna su ID generado.
     */
    public static function createDirector(
        PDO $pdo,
        string $nombre,
        string $apellidos,
        int $plantelId,
        int $licenciaturaId,
        string $turno
    ): int {
        $stmt = $pdo->prepare(
            "INSERT INTO directores (nombre, apellidos, id_plantel, id_licenciatura, turno, estatus)
             VALUES (:nombre, :apellidos, :id_plantel, :id_licenciatura, :turno, 'ACTIVO')"
        );
        $stmt->execute([
            ':nombre'          => trim($nombre),
            ':apellidos'       => trim($apellidos),
            ':id_plantel'      => $plantelId,
            ':id_licenciatura' => $licenciaturaId,
            ':turno'           => strtoupper(trim($turno)),
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Actualiza la información de un director existente.
     *
     * @param array<string, mixed> $data
     */
    public static function updateDirector(PDO $pdo, int $id, array $data): bool
    {
        $allowed = ['nombre', 'apellidos', 'id_plantel', 'id_licenciatura', 'turno', 'estatus'];
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $val) {
            if (in_array($key, $allowed, true)) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = is_string($val) ? trim($val) : $val;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE directores SET ' . implode(', ', $fields) . ' WHERE id_director = :id';
        $stmt = $pdo->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Elimina un director por su ID.
     */
    public static function deleteDirector(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare('DELETE FROM directores WHERE id_director = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Obtiene el detalle de un director por su ID.
     *
     * @return array<string, mixed>|null
     */
    public static function getDirectorById(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT 
                d.id_director,
                d.nombre,
                d.apellidos,
                d.id_licenciatura,
                d.id_plantel,
                d.turno,
                d.estatus,
                d.creado_en,
                p.nombre AS plantel_nombre,
                p.id_region,
                l.nombre AS licenciatura_nombre,
                r.nombre AS region_nombre
             FROM directores d
             LEFT JOIN planteles p ON d.id_plantel = p.id_plantel
             LEFT JOIN licenciaturas l ON d.id_licenciatura = l.id_licenciatura
             LEFT JOIN regiones r ON p.id_region = r.id_region
             WHERE d.id_director = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $director = $stmt->fetch();

        return $director !== false ? $director : null;
    }
}
