<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Models\Periodo;
use App\Services\ExcelImportService;
use App\Services\StatisticsService;
use PDO;

class ImportController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Muestra la interfaz para la carga e importación del archivo de encuestas.
     */
    public function showForm(): void
    {
        Security::requireAuth();

        $periodos = Periodo::getAll($this->pdo);

        $pageTitle = 'Importar Encuestas Excel - Evaluación Directiva';
        $data = [
            'periodos' => $periodos
        ];

        require APP_ROOT . '/views/panel/import.php';
    }

    /**
     * Procesa la carga y lectura del archivo Excel vía AJAX (retorna JSON).
     */
    public function upload(): void
    {
        Security::requireAuth();

        header('Content-Type: application/json; charset=utf-8');

        $csrfToken = (string)($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!Security::validateCsrfToken($csrfToken)) {
            echo json_encode([
                'success' => false,
                'message' => 'Token de seguridad inválido o expirado. Por favor, recargue la página.'
            ]);
            return;
        }

        $periodoId = trim((string)($_POST['id_periodo'] ?? ''));
        if ($periodoId === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Debe seleccionar un periodo de evaluación válido.'
            ]);
            return;
        }

        $periodo = Periodo::findById($this->pdo, $periodoId);
        if ($periodo === null) {
            echo json_encode([
                'success' => false,
                'message' => 'El periodo seleccionado no existe en el sistema.'
            ]);
            return;
        }

        if (!isset($_FILES['archivo_excel']) || $_FILES['archivo_excel']['error'] !== UPLOAD_ERR_OK) {
            $errorCode = $_FILES['archivo_excel']['error'] ?? UPLOAD_ERR_NO_FILE;
            $msg = match ($errorCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido.',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió de forma incompleta.',
                UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo para subir.',
                default => 'Ocurrió un error al recibir el archivo en el servidor.'
            };
            echo json_encode([
                'success' => false,
                'message' => $msg
            ]);
            return;
        }

        $fileTmpPath = (string)$_FILES['archivo_excel']['tmp_name'];
        $fileName = (string)$_FILES['archivo_excel']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, ['xlsx', 'xls'], true)) {
            echo json_encode([
                'success' => false,
                'message' => 'Extensión no permitida. Solo se aceptan archivos Excel (.xlsx o .xls).'
            ]);
            return;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo ? finfo_file($finfo, $fileTmpPath) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        $clientMime = (string)($_FILES['archivo_excel']['type'] ?? '');
        $allowedMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/zip',
            'application/x-zip',
            'application/octet-stream'
        ];

        if (!in_array($detectedMime, $allowedMimes, true) && !in_array($clientMime, $allowedMimes, true)) {
            echo json_encode([
                'success' => false,
                'message' => 'El tipo MIME del archivo no corresponde a una hoja de cálculo Excel válida (' . htmlspecialchars((string)$detectedMime) . ').'
            ]);
            return;
        }

        $uploadDirectory = APP_ROOT . '/storage/uploads';
        if (!is_dir($uploadDirectory)) {
            if (!mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No se pudo crear el directorio de almacenamiento temporal.'
                ]);
                return;
            }
        }

        $sanitizedBase = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
        $newFileName = 'encuestas_' . $periodoId . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $fileExtension;
        $destinationPath = $uploadDirectory . '/' . $newFileName;

        if (!move_uploaded_file($fileTmpPath, $destinationPath)) {
            echo json_encode([
                'success' => false,
                'message' => 'No fue posible guardar el archivo subido en el servidor.'
            ]);
            return;
        }

        try {
            $importService = new ExcelImportService($this->pdo);
            $importResult = $importService->import($destinationPath, $periodoId);

            $statsService = new StatisticsService($this->pdo);
            $statsResult = $statsService->calculateForPeriodo($periodoId);

            echo json_encode([
                'success' => true,
                'message' => 'Archivo procesado y estadísticas calculadas correctamente para el periodo ' . $periodoId . '.',
                'data' => [
                    'import' => $importResult,
                    'stats' => $statsResult
                ]
            ]);
        } catch (\Throwable $e) {
            error_log('Error en procesamiento de Excel: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Ocurrió un error al procesar el archivo: ' . $e->getMessage()
            ]);
        }
    }
}
