<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Modelo para la administración de períodos escolares y su estatus.
 */
class Periodo
{
    /**
     * Obtiene todos los períodos ordenados por fecha de creación descendente.
     *
     * @return array<int, array{id_periodo: string, estatus: string, creado_en: string}>
     */
    public static function getAll(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT id_periodo, estatus, creado_en FROM periodos ORDER BY creado_en DESC');
        return $stmt->fetchAll();
    }

    /**
     * Busca un período por su clave identificadora (ejemplo: '2027-1').
     *
     * @return array{id_periodo: string, estatus: string, creado_en: string}|null
     */
    public static function findById(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT id_periodo, estatus, creado_en FROM periodos WHERE id_periodo = :id LIMIT 1');
        $stmt->execute([':id' => trim($id)]);
        $periodo = $stmt->fetch();

        return $periodo !== false ? $periodo : null;
    }

    /**
     * Crea un nuevo período escolar con estatus 'ACTIVO'.
     */
    public static function create(PDO $pdo, string $id): bool
    {
        $stmt = $pdo->prepare("INSERT INTO periodos (id_periodo, estatus) VALUES (:id, 'ACTIVO')");
        return $stmt->execute([':id' => trim($id)]);
    }

    /**
     * Actualiza el estatus de un período ('ACTIVO' o 'CERRADO').
     */
    public static function updateEstatus(PDO $pdo, string $id, string $estatus): bool
    {
        $stmt = $pdo->prepare('UPDATE periodos SET estatus = :estatus WHERE id_periodo = :id');
        return $stmt->execute([
            ':estatus' => strtoupper(trim($estatus)),
            ':id'      => trim($id),
        ]);
    }

    /**
     * Elimina un período y sus respuestas y resultados asociados de manera segura.
     */
    public static function delete(PDO $pdo, string $id): bool
    {
        $cleanId = trim($id);
        $wasInTransaction = $pdo->inTransaction();

        if (!$wasInTransaction) {
            $pdo->beginTransaction();
        }

        try {
            // Eliminar resultados asociados
            $stmtRes = $pdo->prepare('DELETE FROM resultados_evaluacion WHERE id_periodo = :id');
            $stmtRes->execute([':id' => $cleanId]);

            // Eliminar respuestas asociadas
            $stmtResp = $pdo->prepare('DELETE FROM respuestas_evaluacion WHERE id_periodo = :id');
            $stmtResp->execute([':id' => $cleanId]);

            // Eliminar el período
            $stmtPeriodo = $pdo->prepare('DELETE FROM periodos WHERE id_periodo = :id');
            $result = $stmtPeriodo->execute([':id' => $cleanId]);

            if (!$wasInTransaction) {
                $pdo->commit();
            }

            return $result;
        } catch (\Throwable $e) {
            if (!$wasInTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Comprueba si el período cuenta con respuestas de encuestas registradas.
     */
    public static function hasRespuestas(PDO $pdo, string $id): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM respuestas_evaluacion WHERE id_periodo = :id');
        $stmt->execute([':id' => trim($id)]);

        return ((int)$stmt->fetchColumn()) > 0;
    }

    /**
     * Obtiene un resumen numérico del período: total de respuestas, resultados calculados y promedio general.
     *
     * @return array{id_periodo: string, total_respuestas: int, total_resultados: int, promedio_general: float}
     */
    public static function getResumenByPeriodo(PDO $pdo, string $id): array
    {
        $cleanId = trim($id);

        $stmtResp = $pdo->prepare('SELECT COUNT(*) FROM respuestas_evaluacion WHERE id_periodo = :id');
        $stmtResp->execute([':id' => $cleanId]);
        $totalResp = (int)$stmtResp->fetchColumn();

        $stmtRes = $pdo->prepare(
            'SELECT COUNT(*) AS total_resultados, AVG(promedio_global) AS promedio_general 
             FROM resultados_evaluacion 
             WHERE id_periodo = :id'
        );
        $stmtRes->execute([':id' => $cleanId]);
        $resData = $stmtRes->fetch() ?: [];

        return [
            'id_periodo'       => $cleanId,
            'total_respuestas' => $totalResp,
            'total_resultados' => (int)($resData['total_resultados'] ?? 0),
            'promedio_general' => $resData['promedio_general'] !== null ? round((float)$resData['promedio_general'], 2) : 0.0,
        ];
    }
}
