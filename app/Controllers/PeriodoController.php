<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Models\Catalogo;
use App\Models\Periodo;
use App\Models\Respuesta;
use App\Services\StatisticsService;
use PDO;

class PeriodoController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Muestra la pantalla principal / dashboard del panel.
     */
    public function dashboard(): void
    {
        Security::requireAuth();

        $periodos = Periodo::getAll($this->pdo);
        $directoresActivos = Catalogo::getDirectoresActivos($this->pdo);
        $ultimoPeriodo = !empty($periodos) ? (string)($periodos[0]['id_periodo'] ?? 'N/A') : 'Sin periodos';

        $pageTitle = 'Panel Principal - Evaluación Directiva';
        $data = [
            'periodos_count' => count($periodos),
            'directores_count' => count($directoresActivos),
            'ultimo_periodo' => $ultimoPeriodo
        ];

        require APP_ROOT . '/views/panel/index.php';
    }

    /**
     * Listado y administración de periodos.
     */
    public function index(): void
    {
        Security::requireAuth();

        $periodos = Periodo::getAll($this->pdo);

        // Enriquecer cada periodo con el conteo de respuestas
        foreach ($periodos as &$periodo) {
            $periodoId = (string)($periodo['id_periodo'] ?? '');
            $periodo['respuestas_count'] = Respuesta::getCountByPeriodo($this->pdo, $periodoId);
        }
        unset($periodo);

        $pageTitle = 'Gestión de Periodos - Evaluación Directiva';
        $data = [
            'periodos' => $periodos
        ];

        require APP_ROOT . '/views/panel/periodos.php';
    }

    /**
     * Crea un nuevo periodo de evaluación.
     */
    public function crear(): void
    {
        Security::requireAuth();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o sesión expirada.';
            Security::redirect('periodos');
            return;
        }

        $idPeriodo = strtoupper(trim((string)($_POST['id_periodo'] ?? '')));

        if ($idPeriodo === '') {
            $_SESSION['flash_error'] = 'El identificador del periodo no puede estar vacío.';
            Security::redirect('periodos');
            return;
        }

        if (!preg_match('/^[A-Z0-9\-_]{3,20}$/', $idPeriodo)) {
            $_SESSION['flash_error'] = 'El formato del periodo no es válido (ejemplo: 2027-1).';
            Security::redirect('periodos');
            return;
        }

        try {
            $existente = Periodo::findById($this->pdo, $idPeriodo);
            if ($existente !== null) {
                $_SESSION['flash_error'] = "El periodo '{$idPeriodo}' ya se encuentra registrado.";
                Security::redirect('periodos');
                return;
            }

            $creado = Periodo::create($this->pdo, $idPeriodo);
            if ($creado) {
                $_SESSION['flash_success'] = "El periodo '{$idPeriodo}' fue creado exitosamente.";
            } else {
                $_SESSION['flash_error'] = 'No fue posible crear el periodo. Inténtelo más tarde.';
            }
        } catch (\Throwable $e) {
            error_log('Error al crear periodo: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al procesar la solicitud: ' . $e->getMessage();
        }

        Security::redirect('periodos');
    }

    /**
     * Elimina un periodo y sus datos dependientes.
     */
    public function eliminar(): void
    {
        Security::requireAuth();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido.';
            Security::redirect('periodos');
            return;
        }

        $idPeriodo = trim((string)($_POST['id_periodo'] ?? ''));
        if ($idPeriodo === '') {
            $_SESSION['flash_error'] = 'Identificador de periodo no especificado.';
            Security::redirect('periodos');
            return;
        }

        try {
            $eliminado = Periodo::delete($this->pdo, $idPeriodo);
            if ($eliminado) {
                $_SESSION['flash_success'] = "El periodo '{$idPeriodo}' y sus registros asociados fueron eliminados correctamente.";
            } else {
                $_SESSION['flash_error'] = 'No fue posible eliminar el periodo seleccionado.';
            }
        } catch (\Throwable $e) {
            error_log('Error al eliminar periodo: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error de base de datos: ' . $e->getMessage();
        }

        Security::redirect('periodos');
    }

    /**
     * Muestra la vista de comparativa histórica entre periodos.
     */
    public function comparativa(): void
    {
        Security::requireAuth();

        $periodos = Periodo::getAll($this->pdo);

        $pageTitle = 'Comparativa de Periodos - Evaluación Directiva';
        $data = [
            'periodos' => $periodos
        ];

        require APP_ROOT . '/views/panel/comparativa.php';
    }

    /**
     * Endpoint API para consultar la comparativa entre dos periodos (retorna JSON).
     */
    public function apiComparativa(): void
    {
        Security::requireAuth();

        header('Content-Type: application/json; charset=utf-8');

        $periodo1 = trim((string)($_GET['periodo1'] ?? ''));
        $periodo2 = trim((string)($_GET['periodo2'] ?? ''));

        if ($periodo1 === '' || $periodo2 === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Debe seleccionar dos periodos para realizar la comparación.'
            ]);
            return;
        }

        if ($periodo1 === $periodo2) {
            echo json_encode([
                'success' => false,
                'message' => 'Debe seleccionar periodos diferentes para contrastar.'
            ]);
            return;
        }

        try {
            $statsService = new StatisticsService($this->pdo);
            $comparativa = $statsService->comparePeriodos($periodo1, $periodo2);

            echo json_encode([
                'success' => true,
                'data' => $comparativa
            ]);
        } catch (\Throwable $e) {
            error_log('Error en apiComparativa: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error al calcular la comparativa: ' . $e->getMessage()
            ]);
        }
    }
}
