<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Models\Catalogo;
use PDO;

class CatalogoController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Muestra la vista de administración de catálogos institucionales.
     */
    public function index(): void
    {
        Security::requireAuth();

        $directores = Catalogo::getAllDirectores($this->pdo);
        $planteles = Catalogo::getAllPlanteles($this->pdo);
        $licenciaturas = Catalogo::getAllLicenciaturas($this->pdo);
        $regiones = Catalogo::getAllRegiones($this->pdo);

        $pageTitle = 'Gestión de Catálogos - Evaluación Directiva';
        $data = [
            'directores' => $directores,
            'planteles' => $planteles,
            'licenciaturas' => $licenciaturas,
            'regiones' => $regiones
        ];

        require APP_ROOT . '/views/panel/catalogos.php';
    }

    /**
     * Registra o actualiza la información de un director.
     */
    public function guardarDirector(): void
    {
        Security::requireAuth();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            Security::redirect('catalogos');
            return;
        }

        $idDirector = !empty($_POST['id_director']) ? (int)$_POST['id_director'] : null;
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $apellidos = trim((string)($_POST['apellidos'] ?? ''));
        $plantelId = (int)($_POST['id_plantel'] ?? 0);
        $licId = (int)($_POST['id_licenciatura'] ?? 0);
        $turno = strtoupper(trim((string)($_POST['turno'] ?? 'T')));
        $estatus = strtoupper(trim((string)($_POST['estatus'] ?? 'ACTIVO')));

        $validTurnos = ['M', 'D', 'V', 'N', 'T'];
        if (!in_array($turno, $validTurnos, true)) {
            $turno = 'T';
        }

        $validEstatus = ['ACTIVO', 'INACTIVO'];
        if (!in_array($estatus, $validEstatus, true)) {
            $estatus = 'ACTIVO';
        }

        if ($nombre === '' || $apellidos === '' || $plantelId <= 0 || $licId <= 0) {
            $_SESSION['flash_error'] = 'Por favor complete todos los campos obligatorios del Director.';
            Security::redirect('catalogos');
            return;
        }

        try {
            if ($idDirector !== null && $idDirector > 0) {
                $updateData = [
                    'nombre' => $nombre,
                    'apellidos' => $apellidos,
                    'id_plantel' => $plantelId,
                    'id_licenciatura' => $licId,
                    'turno' => $turno,
                    'estatus' => $estatus
                ];
                $actualizado = Catalogo::updateDirector($this->pdo, $idDirector, $updateData);

                if ($actualizado) {
                    $_SESSION['flash_success'] = 'Director actualizado exitosamente.';
                } else {
                    $_SESSION['flash_error'] = 'No fue posible actualizar los datos del director.';
                }
            } else {
                $nuevoId = Catalogo::createDirector($this->pdo, $nombre, $apellidos, $plantelId, $licId, $turno);

                if ($nuevoId > 0) {
                    $_SESSION['flash_success'] = 'Director registrado exitosamente en el catálogo.';
                } else {
                    $_SESSION['flash_error'] = 'No fue posible registrar al director.';
                }
            }
        } catch (\Throwable $e) {
            error_log('Error en guardarDirector: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al procesar la operación: ' . $e->getMessage();
        }

        Security::redirect('catalogos');
    }

    /**
     * Elimina a un director del catálogo.
     */
    public function eliminarDirector(): void
    {
        Security::requireAuth();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido.';
            Security::redirect('catalogos');
            return;
        }

        $idDirector = (int)($_POST['id_director'] ?? 0);
        if ($idDirector <= 0) {
            $_SESSION['flash_error'] = 'Identificador de director no válido.';
            Security::redirect('catalogos');
            return;
        }

        try {
            $eliminado = Catalogo::deleteDirector($this->pdo, $idDirector);

            if ($eliminado) {
                $_SESSION['flash_success'] = 'Director eliminado correctamente.';
            } else {
                $_SESSION['flash_error'] = 'No se pudo eliminar al director seleccionado.';
            }
        } catch (\Throwable $e) {
            error_log('Error al eliminar director: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error de base de datos: ' . $e->getMessage();
        }

        Security::redirect('catalogos');
    }
}
