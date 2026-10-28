<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Models\Catalogo;
use App\Models\Periodo;
use App\Services\PdfGeneratorService;
use App\Services\StatisticsService;
use PDO;

class ReportController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Muestra la pantalla de resultados, rankings y filtros estadísticos.
     */
    public function index(): void
    {
        Security::requireAuth();

        $periodos = Periodo::getAll($this->pdo);
        $planteles = Catalogo::getAllPlanteles($this->pdo);
        $licenciaturas = Catalogo::getAllLicenciaturas($this->pdo);
        $regiones = Catalogo::getAllRegiones($this->pdo);

        // Determinar periodo seleccionado (por GET o el primero disponible)
        $periodoSeleccionado = isset($_GET['id_periodo']) ? trim((string)$_GET['id_periodo']) : '';
        if ($periodoSeleccionado === '' && !empty($periodos)) {
            $periodoSeleccionado = (string)($periodos[0]['id_periodo'] ?? '');
        }

        $plantelId = !empty($_GET['id_plantel']) ? (int)$_GET['id_plantel'] : null;
        $licId = !empty($_GET['id_licenciatura']) ? (int)$_GET['id_licenciatura'] : null;
        $regionId = !empty($_GET['id_region']) ? (int)$_GET['id_region'] : null;

        $resultados = [];
        $resumen = null;

        if ($periodoSeleccionado !== '') {
            try {
                $statsService = new StatisticsService($this->pdo);
                $resultados = $statsService->getResultadosFiltrados(
                    $periodoSeleccionado,
                    $plantelId,
                    $licId,
                    $regionId
                );
                $resumen = $statsService->getResumenGlobal($periodoSeleccionado);
            } catch (\Throwable $e) {
                error_log('Error al consultar estadísticas en ReportController: ' . $e->getMessage());
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                $_SESSION['flash_error'] = 'No fue posible cargar las estadísticas para el periodo seleccionado.';
            }
        }

        $pageTitle = 'Resultados de Evaluación - Evaluación Directiva';
        $data = [
            'periodos' => $periodos,
            'planteles' => $planteles,
            'licenciaturas' => $licenciaturas,
            'regiones' => $regiones,
            'resultados' => $resultados,
            'resumen' => $resumen,
            'filters' => [
                'id_periodo' => $periodoSeleccionado,
                'id_plantel' => $plantelId,
                'id_licenciatura' => $licId,
                'id_region' => $regionId
            ]
        ];

        require APP_ROOT . '/views/reports/index.php';
    }

    /**
     * Genera y descarga el PDF de Resultados Generales por Región
     */
    public function pdfRegion(): void
    {
        Security::requireAuth();

        $periodoId = isset($_GET['id_periodo']) ? trim((string)$_GET['id_periodo']) : '';
        $regionId = !empty($_GET['id_region']) ? (int)$_GET['id_region'] : null;

        if ($periodoId === '') {
            $_SESSION['flash_error'] = 'Debe seleccionar un periodo de evaluación para generar el PDF.';
            header('Location: ' . url('reportes'));
            exit;
        }

        $pdfService = new PdfGeneratorService($this->pdo);
        $pdfService->generateRegionReport($periodoId, $regionId);
    }

    /**
     * Genera y descarga el PDF Detallado con temas P1..P10 por Plantel
     */
    public function pdfPlantel(): void
    {
        Security::requireAuth();

        $periodoId = isset($_GET['id_periodo']) ? trim((string)$_GET['id_periodo']) : '';
        $plantelId = !empty($_GET['id_plantel']) ? (int)$_GET['id_plantel'] : 0;

        if ($periodoId === '' || $plantelId <= 0) {
            $_SESSION['flash_error'] = 'Debe seleccionar un periodo y un plantel para generar el reporte detallado.';
            header('Location: ' . url('reportes'));
            exit;
        }

        $pdfService = new PdfGeneratorService($this->pdo);
        $pdfService->generatePlantelReport($periodoId, $plantelId);
    }

    /**
     * Alias a dashboard por si el enrutador invoca este controlador para la raíz.
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
}
