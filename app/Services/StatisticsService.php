<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Respuesta;
use Exception;
use PDO;

/**
 * Servicio estadístico y de cálculo de métricas para la Evaluación Directiva.
 */
class StatisticsService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Calcula y almacena las métricas estadísticas para un periodo específico.
     *
     * Pasos:
     * 1. Elimina resultados preexistentes del periodo dentro de una transacción.
     * 2. Agrupa respuestas por director, plantel y licenciatura.
     * 3. Calcula total de encuestas, promedios de p1 a p10, promedio global y desviación estándar poblacional.
     * 4. Inserta los registros consolidados en resultados_evaluacion.
     * 5. Asigna posiciones del ranking considerando empates (1224 ranking).
     * 6. Retorna resumen del cálculo.
     *
     * @param string $periodoId Identificador del periodo (e.g. '2026-3').
     * @return array Resumen ['total_directors' => int, 'total_responses' => int, 'rankings' => array]
     * @throws Exception Si ocurre un fallo en el cálculo o almacenamiento en base de datos.
     */
    public function calculateForPeriodo(string $periodoId): array
    {
        $this->pdo->beginTransaction();

        try {
            // 1. Eliminar resultados existentes para el periodo
            if (class_exists(Respuesta::class) && method_exists(Respuesta::class, 'deleteResultadosByPeriodo')) {
                Respuesta::deleteResultadosByPeriodo($this->pdo, $periodoId);
            } else {
                $stmtDel = $this->pdo->prepare("DELETE FROM resultados_evaluacion WHERE id_periodo = :periodo");
                $stmtDel->execute(['periodo' => $periodoId]);
            }

            // 2. Consultar y consolidar respuestas por director, plantel y licenciatura
            // Desviación estándar poblacional calculada sobre el promedio individual de cada encuesta
            $sqlGroup = "SELECT 
                            r.id_director,
                            r.id_plantel,
                            r.id_licenciatura,
                            COALESCE(d.turno, 'T') AS turno_director,
                            COUNT(r.id_respuesta) AS total_encuestas,
                            AVG(r.p1_horario_cubiculo) AS p1_prom,
                            AVG(r.p2_atencion_respetuosa) AS p2_prom,
                            AVG(r.p3_reinscripcion_personal) AS p3_prom,
                            AVG(r.p4_semblanza) AS p4_prom,
                            AVG(r.p5_actividad_extracurricular) AS p5_prom,
                            AVG(r.p6_maestros_activos) AS p6_prom,
                            AVG(r.p7_acciones_disciplinarias) AS p7_prom,
                            AVG(r.p8_observaciones_maestros) AS p8_prom,
                            AVG(r.p9_denota_liderazgo) AS p9_prom,
                            AVG(r.p10_evaluacion_general) AS p10_prom,
                            STDDEV_POP((
                                r.p1_horario_cubiculo + r.p2_atencion_respetuosa + r.p3_reinscripcion_personal +
                                r.p4_semblanza + r.p5_actividad_extracurricular + r.p6_maestros_activos +
                                r.p7_acciones_disciplinarias + r.p8_observaciones_maestros +
                                r.p9_denota_liderazgo + r.p10_evaluacion_general
                            ) / 10.0) AS desviacion_estandar
                        FROM respuestas_evaluacion r
                        INNER JOIN directores d ON r.id_director = d.id_director
                        WHERE r.id_periodo = :periodo
                          AND r.id_director IS NOT NULL
                        GROUP BY r.id_director, r.id_plantel, r.id_licenciatura, d.turno";

            $stmtGroup = $this->pdo->prepare($sqlGroup);
            $stmtGroup->execute(['periodo' => $periodoId]);
            $groupedRows = $stmtGroup->fetchAll(PDO::FETCH_ASSOC);

            if (empty($groupedRows)) {
                $this->pdo->commit();
                return [
                    'total_directors' => 0,
                    'total_responses' => 0,
                    'rankings' => [],
                ];
            }

            // 3. Preparar inserción de resultados
            $sqlInsert = "INSERT INTO resultados_evaluacion (
                            id_periodo, id_director, id_licenciatura, id_plantel, turno,
                            total_encuestas, p1_prom, p2_prom, p3_prom, p4_prom, p5_prom,
                            p6_prom, p7_prom, p8_prom, p9_prom, p10_prom,
                            promedio_global, desviacion_estandar, posicion_ranking, fecha_calculo
                        ) VALUES (
                            :id_periodo, :id_director, :id_licenciatura, :id_plantel, :turno,
                            :total_encuestas, :p1_prom, :p2_prom, :p3_prom, :p4_prom, :p5_prom,
                            :p6_prom, :p7_prom, :p8_prom, :p9_prom, :p10_prom,
                            :promedio_global, :desviacion_estandar, :posicion_ranking, NOW()
                        )";
            $stmtInsert = $this->pdo->prepare($sqlInsert);

            $insertedResults = [];
            $totalResponsesCount = 0;

            foreach ($groupedRows as $row) {
                $totalEncuestas = (int)$row['total_encuestas'];
                $totalResponsesCount += $totalEncuestas;

                $p1 = round((float)$row['p1_prom'], 2);
                $p2 = round((float)$row['p2_prom'], 2);
                $p3 = round((float)$row['p3_prom'], 2);
                $p4 = round((float)$row['p4_prom'], 2);
                $p5 = round((float)$row['p5_prom'], 2);
                $p6 = round((float)$row['p6_prom'], 2);
                $p7 = round((float)$row['p7_prom'], 2);
                $p8 = round((float)$row['p8_prom'], 2);
                $p9 = round((float)$row['p9_prom'], 2);
                $p10 = round((float)$row['p10_prom'], 2);

                $promedioGlobal = round(($p1 + $p2 + $p3 + $p4 + $p5 + $p6 + $p7 + $p8 + $p9 + $p10) / 10.0, 2);
                $desviacion = isset($row['desviacion_estandar']) && $row['desviacion_estandar'] !== null
                    ? round((float)$row['desviacion_estandar'], 2)
                    : 0.00;

                $data = [
                    'id_periodo' => $periodoId,
                    'id_director' => (int)$row['id_director'],
                    'id_licenciatura' => (int)$row['id_licenciatura'],
                    'id_plantel' => (int)$row['id_plantel'],
                    'turno' => (string)$row['turno_director'],
                    'total_encuestas' => $totalEncuestas,
                    'p1_prom' => $p1,
                    'p2_prom' => $p2,
                    'p3_prom' => $p3,
                    'p4_prom' => $p4,
                    'p5_prom' => $p5,
                    'p6_prom' => $p6,
                    'p7_prom' => $p7,
                    'p8_prom' => $p8,
                    'p9_prom' => $p9,
                    'p10_prom' => $p10,
                    'promedio_global' => $promedioGlobal,
                    'desviacion_estandar' => $desviacion,
                    'posicion_ranking' => null,
                ];

                if (class_exists(Respuesta::class) && method_exists(Respuesta::class, 'insertResultado')) {
                    $insertedId = Respuesta::insertResultado($this->pdo, $data);
                } else {
                    $stmtInsert->execute($data);
                    $insertedId = (int)$this->pdo->lastInsertId();
                }

                $insertedResults[] = [
                    'id_resultado' => $insertedId,
                    'promedio_global' => $promedioGlobal,
                ];
            }

            // 4. Calcular e inyectar el ranking con soporte para empates
            usort($insertedResults, function (array $a, array $b): int {
                if (abs($a['promedio_global'] - $b['promedio_global']) < 0.0001) {
                    return 0;
                }
                return ($b['promedio_global'] > $a['promedio_global']) ? 1 : -1;
            });

            $stmtUpdateRanking = $this->pdo->prepare("UPDATE resultados_evaluacion SET posicion_ranking = :pos WHERE id_resultado = :id");

            $currentRank = 1;
            $rankIndex = 1;
            $lastScore = null;

            foreach ($insertedResults as $res) {
                $score = (float)$res['promedio_global'];
                if ($lastScore !== null && abs($score - $lastScore) < 0.0001) {
                    $pos = $currentRank;
                } else {
                    $pos = $rankIndex;
                    $currentRank = $rankIndex;
                    $lastScore = $score;
                }
                $rankIndex++;

                $stmtUpdateRanking->execute([
                    'pos' => $pos,
                    'id' => (int)$res['id_resultado'],
                ]);
            }

            // 5. Consultar los resultados finales ordenados por ranking
            $finalRankings = $this->getResultadosFiltrados($periodoId);

            $this->pdo->commit();

            return [
                'total_directors' => count($finalRankings),
                'total_responses' => $totalResponsesCount,
                'rankings' => $finalRankings,
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new Exception("Error al calcular estadísticas del periodo '{$periodoId}': " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Compara los resultados de evaluación entre dos periodos académicos.
     * Realiza un emparejamiento por director y calcula variaciones y tendencias.
     *
     * @param string $periodoId1 Identificador del periodo base (e.g. '2026-3').
     * @param string $periodoId2 Identificador del periodo posterior (e.g. '2027-1').
     * @return array Estructura con datos de ambos periodos y comparativa detallada.
     */
    public function comparePeriodos(string $periodoId1, string $periodoId2): array
    {
        $res1 = $this->getResultadosFiltrados($periodoId1);
        $res2 = $this->getResultadosFiltrados($periodoId2);

        $map1 = [];
        foreach ($res1 as $r) {
            $map1[(int)$r['id_director']] = $r;
        }

        $map2 = [];
        foreach ($res2 as $r) {
            $map2[(int)$r['id_director']] = $r;
        }

        $allDirectorIds = array_unique(array_merge(array_keys($map1), array_keys($map2)));
        $comparativa = [];

        foreach ($allDirectorIds as $dirId) {
            $item1 = $map1[$dirId] ?? null;
            $item2 = $map2[$dirId] ?? null;

            $directorNombre = $item2['director_nombre'] ?? $item1['director_nombre'] ?? "Director #{$dirId}";
            $plantelNombre = $item2['plantel_nombre'] ?? $item1['plantel_nombre'] ?? 'N/A';
            $licenciaturaNombre = $item2['licenciatura_nombre'] ?? $item1['licenciatura_nombre'] ?? 'N/A';
            $licenciaturaNomenclatura = $item2['licenciatura_nomenclatura'] ?? $item1['licenciatura_nomenclatura'] ?? '';

            $p1Prom = ($item1 !== null) ? (float)$item1['promedio_global'] : null;
            $p2Prom = ($item2 !== null) ? (float)$item2['promedio_global'] : null;

            if ($p1Prom !== null && $p2Prom !== null) {
                $diferencia = round($p2Prom - $p1Prom, 2);
                if ($diferencia > 0.001) {
                    $tendencia = 'up';
                } elseif ($diferencia < -0.001) {
                    $tendencia = 'down';
                } else {
                    $tendencia = 'equal';
                }
            } else {
                $diferencia = null;
                $tendencia = 'equal';
            }

            $comparativa[] = [
                'id_director' => $dirId,
                'director' => $directorNombre,
                'plantel' => $plantelNombre,
                'licenciatura' => $licenciaturaNombre,
                'nomenclatura' => $licenciaturaNomenclatura,
                'periodo1_promedio' => $p1Prom,
                'periodo2_promedio' => $p2Prom,
                'diferencia' => $diferencia,
                'tendencia' => $tendencia,
            ];
        }

        // Ordenar comparativa: directores presentes en ambos periodos primero por mayor incremento
        usort($comparativa, function (array $a, array $b): int {
            $hasBothA = ($a['periodo1_promedio'] !== null && $a['periodo2_promedio'] !== null);
            $hasBothB = ($b['periodo1_promedio'] !== null && $b['periodo2_promedio'] !== null);

            if ($hasBothA && !$hasBothB) {
                return -1;
            }
            if (!$hasBothA && $hasBothB) {
                return 1;
            }

            if ($hasBothA && $hasBothB) {
                if ($a['diferencia'] == $b['diferencia']) {
                    return strcmp($a['director'], $b['director']);
                }
                return ($b['diferencia'] > $a['diferencia']) ? 1 : -1;
            }

            return strcmp($a['director'], $b['director']);
        });

        return [
            'periodo1' => [
                'id' => $periodoId1,
                'resultados' => $res1,
            ],
            'periodo2' => [
                'id' => $periodoId2,
                'resultados' => $res2,
            ],
            'comparativa' => $comparativa,
        ];
    }

    /**
     * Obtiene resultados de evaluación aplicando filtros dinámicos por plantel, licenciatura o región.
     *
     * @param string $periodoId Identificador del periodo.
     * @param int|null $plantelId ID del plantel (opcional).
     * @param int|null $licenciaturaId ID de la licenciatura (opcional).
     * @param int|null $regionId ID de la región geográfica (opcional).
     * @return array Listado de resultados ordenados por ranking.
     */
    public function getResultadosFiltrados(
        string $periodoId,
        ?int $plantelId = null,
        ?int $licenciaturaId = null,
        ?int $regionId = null
    ): array {
        $sql = "SELECT 
                    r.id_resultado,
                    r.id_periodo,
                    r.id_director,
                    r.id_plantel,
                    r.id_licenciatura,
                    r.turno,
                    r.total_encuestas,
                    r.p1_prom,
                    r.p2_prom,
                    r.p3_prom,
                    r.p4_prom,
                    r.p5_prom,
                    r.p6_prom,
                    r.p7_prom,
                    r.p8_prom,
                    r.p9_prom,
                    r.p10_prom,
                    r.promedio_global,
                    r.desviacion_estandar,
                    r.posicion_ranking,
                    r.fecha_calculo,
                    TRIM(CONCAT(d.nombre, ' ', d.apellidos)) AS director_nombre,
                    p.nombre AS plantel_nombre,
                    p.id_region,
                    l.nombre AS licenciatura_nombre,
                    l.nomenclatura AS licenciatura_nomenclatura
                FROM resultados_evaluacion r
                INNER JOIN directores d ON r.id_director = d.id_director
                INNER JOIN planteles p ON r.id_plantel = p.id_plantel
                INNER JOIN licenciaturas l ON r.id_licenciatura = l.id_licenciatura
                WHERE r.id_periodo = :periodoId";

        $params = ['periodoId' => $periodoId];

        if ($plantelId !== null) {
            $sql .= " AND r.id_plantel = :plantelId";
            $params['plantelId'] = $plantelId;
        }

        if ($licenciaturaId !== null) {
            $sql .= " AND r.id_licenciatura = :licenciaturaId";
            $params['licenciaturaId'] = $licenciaturaId;
        }

        if ($regionId !== null) {
            $sql .= " AND p.id_region = :regionId";
            $params['regionId'] = $regionId;
        }

        $sql .= " ORDER BY r.posicion_ranking ASC, r.promedio_global DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Genera un resumen ejecutivo global de las estadísticas del periodo.
     * Incluye promedios generales, preguntas p1-p10, mejor y peor director evaluado.
     *
     * @param string $periodoId Identificador del periodo.
     * @return array Resumen global estadístico.
     */
    public function getResumenGlobal(string $periodoId): array
    {
        $resultados = $this->getResultadosFiltrados($periodoId);

        if (empty($resultados)) {
            return [
                'periodo' => $periodoId,
                'total_directores' => 0,
                'total_respuestas' => 0,
                'promedio_general' => 0.0,
                'mejor_evaluado' => null,
                'peor_evaluado' => null,
                'promedios_preguntas' => [
                    'p1' => 0.0, 'p2' => 0.0, 'p3' => 0.0, 'p4' => 0.0, 'p5' => 0.0,
                    'p6' => 0.0, 'p7' => 0.0, 'p8' => 0.0, 'p9' => 0.0, 'p10' => 0.0,
                ],
            ];
        }

        $totalDirectores = count($resultados);
        $totalRespuestas = 0;
        $sumPromedioGlobal = 0.0;
        $sumP = array_fill(1, 10, 0.0);

        foreach ($resultados as $row) {
            $totalRespuestas += (int)$row['total_encuestas'];
            $sumPromedioGlobal += (float)$row['promedio_global'];
            for ($i = 1; $i <= 10; $i++) {
                $sumP[$i] += (float)$row["p{$i}_prom"];
            }
        }

        $promedioGeneral = round($sumPromedioGlobal / $totalDirectores, 2);

        $promediosPreguntas = [];
        for ($i = 1; $i <= 10; $i++) {
            $promediosPreguntas["p{$i}"] = round($sumP[$i] / $totalDirectores, 2);
        }

        // Ya que la lista viene ordenada por ranking ASC (mejores puntuaciones primero)
        $mejorEvaluado = $resultados[0] ?? null;
        $peorEvaluado = $resultados[$totalDirectores - 1] ?? null;

        return [
            'periodo' => $periodoId,
            'total_directores' => $totalDirectores,
            'total_respuestas' => $totalRespuestas,
            'promedio_general' => $promedioGeneral,
            'mejor_evaluado' => $mejorEvaluado,
            'peor_evaluado' => $peorEvaluado,
            'promedios_preguntas' => $promediosPreguntas,
        ];
    }

    /**
     * Obtiene el ranking de los N directores mejor evaluados.
     *
     * @param string $periodoId Identificador del periodo.
     * @param int $limit Cantidad máxima de registros.
     * @return array Ranking superior.
     */
    public function getRanking(string $periodoId, int $limit = 10): array
    {
        $sql = "SELECT 
                    r.id_resultado,
                    r.id_periodo,
                    r.id_director,
                    r.id_plantel,
                    r.id_licenciatura,
                    r.turno,
                    r.total_encuestas,
                    r.promedio_global,
                    r.desviacion_estandar,
                    r.posicion_ranking,
                    TRIM(CONCAT(d.nombre, ' ', d.apellidos)) AS director_nombre,
                    p.nombre AS plantel_nombre,
                    l.nombre AS licenciatura_nombre,
                    l.nomenclatura AS licenciatura_nomenclatura
                FROM resultados_evaluacion r
                INNER JOIN directores d ON r.id_director = d.id_director
                INNER JOIN planteles p ON r.id_plantel = p.id_plantel
                INNER JOIN licenciaturas l ON r.id_licenciatura = l.id_licenciatura
                WHERE r.id_periodo = :periodo
                ORDER BY r.posicion_ranking ASC, r.promedio_global DESC
                LIMIT :lim";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':periodo', $periodoId, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los resultados detallados de un director específico en un periodo.
     *
     * @param string $periodoId Identificador del periodo.
     * @param int $directorId ID del director.
     * @return array|null Datos del resultado o null si no existe.
     */
    public function getDetalleDirector(string $periodoId, int $directorId): ?array
    {
        $sql = "SELECT 
                    r.*,
                    TRIM(CONCAT(d.nombre, ' ', d.apellidos)) AS director_nombre,
                    p.nombre AS plantel_nombre,
                    p.id_region,
                    l.nombre AS licenciatura_nombre,
                    l.nomenclatura AS licenciatura_nomenclatura
                FROM resultados_evaluacion r
                INNER JOIN directores d ON r.id_director = d.id_director
                INNER JOIN planteles p ON r.id_plantel = p.id_plantel
                INNER JOIN licenciaturas l ON r.id_licenciatura = l.id_licenciatura
                WHERE r.id_periodo = :periodo AND r.id_director = :directorId
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'periodo' => $periodoId,
            'directorId' => $directorId,
        ]);

        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Elimina los resultados consolidados de un periodo.
     *
     * @param string $periodoId Identificador del periodo.
     * @return int Cantidad de registros eliminados.
     */
    public function deleteResultados(string $periodoId): int
    {
        if (class_exists(Respuesta::class) && method_exists(Respuesta::class, 'deleteResultadosByPeriodo')) {
            return Respuesta::deleteResultadosByPeriodo($this->pdo, $periodoId);
        }

        $stmt = $this->pdo->prepare("DELETE FROM resultados_evaluacion WHERE id_periodo = :periodo");
        $stmt->execute(['periodo' => $periodoId]);
        return $stmt->rowCount();
    }
}
