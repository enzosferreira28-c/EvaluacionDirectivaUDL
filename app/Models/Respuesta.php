<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Modelo para las respuestas individuales de evaluación y el almacenamiento de resultados consolidados.
 */
class Respuesta
{
    /**
     * Inserta un conjunto de respuestas de evaluación en un lote seguro dentro de una transacción.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return int Cantidad de registros insertados
     */
    public static function insertBatch(PDO $pdo, array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $sql = 'INSERT INTO respuestas_evaluacion 
                (id_periodo, id_plantel, id_licenciatura, id_director, grupo, turno, marca_temporal, 
                 p1_horario_cubiculo, p2_atencion_respetuosa, p3_reinscripcion_personal, 
                 p4_semblanza, p5_actividad_extracurricular, p6_maestros_activos, 
                 p7_acciones_disciplinarias, p8_observaciones_maestros, 
                 p9_denota_liderazgo, p10_evaluacion_general) 
                VALUES 
                (:id_periodo, :id_plantel, :id_licenciatura, :id_director, :grupo, :turno, :marca_temporal, 
                 :p1, :p2, :p3, :p4, :p5, :p6, :p7, :p8, :p9, :p10)';

        $stmt = $pdo->prepare($sql);

        $wasInTransaction = $pdo->inTransaction();
        if (!$wasInTransaction) {
            $pdo->beginTransaction();
        }

        $insertedCount = 0;

        try {
            foreach ($rows as $row) {
                $stmt->execute([
                    ':id_periodo'      => (string)$row['id_periodo'],
                    ':id_plantel'      => (int)$row['id_plantel'],
                    ':id_licenciatura' => (int)$row['id_licenciatura'],
                    ':id_director'     => !empty($row['id_director']) ? (int)$row['id_director'] : null,
                    ':grupo'           => isset($row['grupo']) ? (string)$row['grupo'] : null,
                    ':turno'           => (string)$row['turno'],
                    ':marca_temporal'  => $row['marca_temporal'] ?? date('Y-m-d H:i:s'),
                    ':p1'              => (int)($row['p1_horario_cubiculo'] ?? $row['p1'] ?? 5),
                    ':p2'              => (int)($row['p2_atencion_respetuosa'] ?? $row['p2'] ?? 5),
                    ':p3'              => (int)($row['p3_reinscripcion_personal'] ?? $row['p3'] ?? 5),
                    ':p4'              => (int)($row['p4_semblanza'] ?? $row['p4'] ?? 5),
                    ':p5'              => (int)($row['p5_actividad_extracurricular'] ?? $row['p5'] ?? 5),
                    ':p6'              => (int)($row['p6_maestros_activos'] ?? $row['p6'] ?? 5),
                    ':p7'              => (int)($row['p7_acciones_disciplinarias'] ?? $row['p7'] ?? 5),
                    ':p8'              => (int)($row['p8_observaciones_maestros'] ?? $row['p8'] ?? 5),
                    ':p9'              => (int)($row['p9_denota_liderazgo'] ?? $row['p9'] ?? 5),
                    ':p10'             => (int)($row['p10_evaluacion_general'] ?? $row['p10'] ?? 5),
                ]);
                $insertedCount++;
            }

            if (!$wasInTransaction) {
                $pdo->commit();
            }

            return $insertedCount;
        } catch (\Throwable $e) {
            if (!$wasInTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Elimina todas las respuestas asociadas a un período específico.
     *
     * @return int Número de filas eliminadas
     */
    public static function deleteByPeriodo(PDO $pdo, string $periodoId): int
    {
        $stmt = $pdo->prepare('DELETE FROM respuestas_evaluacion WHERE id_periodo = :id_periodo');
        $stmt->execute([':id_periodo' => trim($periodoId)]);

        return $stmt->rowCount();
    }

    /**
     * Retorna el conteo total de encuestas registradas en un período.
     */
    public static function getCountByPeriodo(PDO $pdo, string $periodoId): int
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM respuestas_evaluacion WHERE id_periodo = :id_periodo');
        $stmt->execute([':id_periodo' => trim($periodoId)]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Obtiene todas las respuestas individuales asignadas a un director en un período dado.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getByDirectorAndPeriodo(PDO $pdo, int $directorId, string $periodoId): array
    {
        $stmt = $pdo->prepare(
            'SELECT * FROM respuestas_evaluacion 
             WHERE id_director = :id_director AND id_periodo = :id_periodo 
             ORDER BY id_respuesta ASC'
        );
        $stmt->execute([
            ':id_director' => $directorId,
            ':id_periodo'  => trim($periodoId),
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene los datos agrupados para el cálculo estadístico por director, plantel, licenciatura y turno consolidado.
     * Incluye también grupos donde id_director es nulo.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getGroupedByDirector(PDO $pdo, string $periodoId): array
    {
        $sql = "SELECT 
                    r.id_director,
                    r.id_plantel,
                    r.id_licenciatura,
                    CASE WHEN r.turno IN ('M', 'D') THEN 'M' ELSE 'T' END AS turno,
                    COUNT(*) AS total_encuestas,
                    ROUND(AVG(r.p1_horario_cubiculo), 2) AS p1_prom,
                    ROUND(AVG(r.p2_atencion_respetuosa), 2) AS p2_prom,
                    ROUND(AVG(r.p3_reinscripcion_personal), 2) AS p3_prom,
                    ROUND(AVG(r.p4_semblanza), 2) AS p4_prom,
                    ROUND(AVG(r.p5_actividad_extracurricular), 2) AS p5_prom,
                    ROUND(AVG(r.p6_maestros_activos), 2) AS p6_prom,
                    ROUND(AVG(r.p7_acciones_disciplinarias), 2) AS p7_prom,
                    ROUND(AVG(r.p8_observaciones_maestros), 2) AS p8_prom,
                    ROUND(AVG(r.p9_denota_liderazgo), 2) AS p9_prom,
                    ROUND(AVG(r.p10_evaluacion_general), 2) AS p10_prom,
                    ROUND(AVG((
                        r.p1_horario_cubiculo + r.p2_atencion_respetuosa + r.p3_reinscripcion_personal +
                        r.p4_semblanza + r.p5_actividad_extracurricular + r.p6_maestros_activos +
                        r.p7_acciones_disciplinarias + r.p8_observaciones_maestros +
                        r.p9_denota_liderazgo + r.p10_evaluacion_general
                    ) / 10.0), 2) AS promedio_global,
                    COALESCE(STDDEV_POP((
                        r.p1_horario_cubiculo + r.p2_atencion_respetuosa + r.p3_reinscripcion_personal +
                        r.p4_semblanza + r.p5_actividad_extracurricular + r.p6_maestros_activos +
                        r.p7_acciones_disciplinarias + r.p8_observaciones_maestros +
                        r.p9_denota_liderazgo + r.p10_evaluacion_general
                    ) / 10.0), 0.00) AS desviacion_estandar,
                    d.nombre AS director_nombre,
                    d.apellidos AS director_apellidos,
                    p.nombre AS plantel_nombre,
                    p.id_region,
                    reg.nombre AS region_nombre,
                    l.nombre AS licenciatura_nombre,
                    l.nomenclatura AS licenciatura_nomenclatura
                FROM respuestas_evaluacion r
                LEFT JOIN directores d ON r.id_director = d.id_director
                LEFT JOIN planteles p ON r.id_plantel = p.id_plantel
                LEFT JOIN licenciaturas l ON r.id_licenciatura = l.id_licenciatura
                LEFT JOIN regiones reg ON p.id_region = reg.id_region
                WHERE r.id_periodo = :id_periodo
                GROUP BY 
                    r.id_director,
                    r.id_plantel,
                    r.id_licenciatura,
                    CASE WHEN r.turno IN ('M', 'D') THEN 'M' ELSE 'T' END
                ORDER BY promedio_global DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_periodo' => trim($periodoId)]);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene el detalle individual de cada encuesta del período con nombres de plantel, licenciatura y director.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getByPeriodoWithDetails(PDO $pdo, string $periodoId): array
    {
        $sql = 'SELECT 
                    r.*,
                    p.nombre AS plantel_nombre,
                    l.nombre AS licenciatura_nombre,
                    d.nombre AS director_nombre,
                    d.apellidos AS director_apellidos,
                    reg.nombre AS region_nombre
                FROM respuestas_evaluacion r
                LEFT JOIN planteles p ON r.id_plantel = p.id_plantel
                LEFT JOIN licenciaturas l ON r.id_licenciatura = l.id_licenciatura
                LEFT JOIN directores d ON r.id_director = d.id_director
                LEFT JOIN regiones reg ON p.id_region = reg.id_region
                WHERE r.id_periodo = :id_periodo
                ORDER BY r.id_respuesta ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_periodo' => trim($periodoId)]);

        return $stmt->fetchAll();
    }

    // ==========================================
    // RESULTADOS DE EVALUACIÓN
    // ==========================================

    /**
     * Inserta un resultado estadístico consolidado y retorna su ID asignado.
     *
     * @param array<string, mixed> $data
     */
    public static function insertResultado(PDO $pdo, array $data): int
    {
        $sql = 'INSERT INTO resultados_evaluacion 
                (id_periodo, id_director, id_licenciatura, id_plantel, turno, total_encuestas, 
                 p1_prom, p2_prom, p3_prom, p4_prom, p5_prom, p6_prom, p7_prom, p8_prom, p9_prom, p10_prom, 
                 promedio_global, desviacion_estandar, posicion_ranking)
                VALUES 
                (:id_periodo, :id_director, :id_licenciatura, :id_plantel, :turno, :total_encuestas, 
                 :p1_prom, :p2_prom, :p3_prom, :p4_prom, :p5_prom, :p6_prom, :p7_prom, :p8_prom, :p9_prom, :p10_prom, 
                 :promedio_global, :desviacion_estandar, :posicion_ranking)';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_periodo'          => (string)$data['id_periodo'],
            ':id_director'         => !empty($data['id_director']) ? (int)$data['id_director'] : null,
            ':id_licenciatura'     => (int)$data['id_licenciatura'],
            ':id_plantel'          => (int)$data['id_plantel'],
            ':turno'               => (string)$data['turno'],
            ':total_encuestas'     => (int)$data['total_encuestas'],
            ':p1_prom'             => (float)$data['p1_prom'],
            ':p2_prom'             => (float)$data['p2_prom'],
            ':p3_prom'             => (float)$data['p3_prom'],
            ':p4_prom'             => (float)$data['p4_prom'],
            ':p5_prom'             => (float)$data['p5_prom'],
            ':p6_prom'             => (float)$data['p6_prom'],
            ':p7_prom'             => (float)$data['p7_prom'],
            ':p8_prom'             => (float)$data['p8_prom'],
            ':p9_prom'             => (float)$data['p9_prom'],
            ':p10_prom'            => (float)$data['p10_prom'],
            ':promedio_global'     => (float)$data['promedio_global'],
            ':desviacion_estandar' => (float)($data['desviacion_estandar'] ?? 0.0),
            ':posicion_ranking'    => (int)($data['posicion_ranking'] ?? 0),
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Elimina los resultados estadísticos calculados de un período.
     *
     * @return int Cantidad de filas eliminadas
     */
    public static function deleteResultadosByPeriodo(PDO $pdo, string $periodoId): int
    {
        $stmt = $pdo->prepare('DELETE FROM resultados_evaluacion WHERE id_periodo = :id_periodo');
        $stmt->execute([':id_periodo' => trim($periodoId)]);

        return $stmt->rowCount();
    }

    /**
     * Obtiene los resultados calculados de un período con filtros opcionales (plantel, licenciatura, región, turno).
     *
     * @param array<string, mixed>|null $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getResultadosByPeriodo(PDO $pdo, string $periodoId, ?array $filters = null): array
    {
        $sql = 'SELECT 
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
                WHERE res.id_periodo = :id_periodo';

        $params = [':id_periodo' => trim($periodoId)];

        if (!empty($filters['id_plantel'])) {
            $sql .= ' AND res.id_plantel = :id_plantel';
            $params[':id_plantel'] = (int)$filters['id_plantel'];
        }

        if (!empty($filters['id_licenciatura'])) {
            $sql .= ' AND res.id_licenciatura = :id_licenciatura';
            $params[':id_licenciatura'] = (int)$filters['id_licenciatura'];
        }

        if (!empty($filters['id_region'])) {
            $sql .= ' AND p.id_region = :id_region';
            $params[':id_region'] = (int)$filters['id_region'];
        }

        if (!empty($filters['turno'])) {
            $sql .= ' AND res.turno = :turno';
            $params[':turno'] = strtoupper(trim((string)$filters['turno']));
        }

        $sql .= ' ORDER BY res.posicion_ranking ASC, res.promedio_global DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene los resultados de un período indexados por ID de director para agilizar comparativas inter-período.
     *
     * @return array<string|int, array<string, mixed>>
     */
    public static function getResultadosByPeriodoForComparison(PDO $pdo, string $periodoId): array
    {
        $resultados = self::getResultadosByPeriodo($pdo, $periodoId);
        $comparativa = [];

        foreach ($resultados as $row) {
            $clave = !empty($row['id_director'])
                ? (int)$row['id_director']
                : ($row['id_plantel'] . '_' . $row['id_licenciatura'] . '_' . $row['turno']);

            $comparativa[$clave] = $row;
        }

        return $comparativa;
    }
}
