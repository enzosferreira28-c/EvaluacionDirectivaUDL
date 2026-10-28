<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Models\Usuario;
use PDO;

class AuthController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Muestra la pantalla de inicio de sesión o redirige si ya está autenticado.
     */
    public function showLogin(): void
    {
        if (Security::isAuthenticated()) {
            Security::redirect('dashboard');
            return;
        }

        $pageTitle = 'Iniciar Sesión - Evaluación Directiva';
        $data = [
            'error' => $_SESSION['flash_error'] ?? null,
            'email' => ''
        ];
        unset($_SESSION['flash_error']);

        require APP_ROOT . '/views/auth/login.php';
    }

    /**
     * Procesa la solicitud de inicio de sesión.
     */
    public function login(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (Security::isAuthenticated()) {
            Security::redirect('dashboard');
            return;
        }

        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        if (!Security::validateCsrfToken($csrfToken)) {
            $pageTitle = 'Iniciar Sesión - Evaluación Directiva';
            $data = [
                'error' => 'Token de seguridad inválido o expirado. Por favor, recargue e intente nuevamente.',
                'email' => trim((string)($_POST['email'] ?? ''))
            ];
            require APP_ROOT . '/views/auth/login.php';
            return;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $pageTitle = 'Iniciar Sesión - Evaluación Directiva';
            $data = [
                'error' => 'Por favor, ingrese su correo electrónico y contraseña.',
                'email' => $email
            ];
            require APP_ROOT . '/views/auth/login.php';
            return;
        }

        try {
            $usuario = Usuario::findByEmail($this->pdo, $email);

            if ($usuario !== null && isset($usuario['password_hash'])) {
                if (Security::verifyPassword($password, (string)$usuario['password_hash'])) {
                    Security::login(
                        (int)$usuario['id_usuario'],
                        (string)$usuario['nombre'],
                        (string)$usuario['email']
                    );

                    $_SESSION['flash_success'] = '¡Bienvenido(a), ' . htmlspecialchars((string)$usuario['nombre']) . '!';
                    Security::redirect('dashboard');
                    return;
                }
            }
        } catch (\Throwable $e) {
            error_log('Error en autenticación: ' . $e->getMessage());
        }

        $pageTitle = 'Iniciar Sesión - Evaluación Directiva';
        $data = [
            'error' => 'Correo electrónico o contraseña incorrectos.',
            'email' => $email
        ];
        require APP_ROOT . '/views/auth/login.php';
    }

    /**
     * Cierra la sesión activa y redirige al login.
     */
    public function logout(): void
    {
        Security::logout();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['flash_success'] = 'Ha cerrado su sesión de manera segura.';
        Security::redirect('login');
    }

    /**
     * Muestra la vista institucional para cambiar la contraseña del usuario actual.
     */
    public function showCambiarPassword(): void
    {
        Security::requireAuth();

        $pageTitle = 'Cambiar Contraseña - Universidad de León';
        $data = [
            'user' => Security::getCurrentUser(),
        ];

        require APP_ROOT . '/views/auth/cambiar_password.php';
    }

    /**
     * Procesa la actualización de la contraseña del usuario logueado.
     */
    public function cambiarPassword(): void
    {
        Security::requireAuth();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado. Por favor, reintente.';
            Security::redirect('cambiar-password');
            return;
        }

        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        // 1. Validar campos no vacíos
        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $_SESSION['flash_error'] = 'Por favor complete todos los campos requeridos.';
            Security::redirect('cambiar-password');
            return;
        }

        // 2. Validar longitud mínima
        if (mb_strlen($newPassword) < 6) {
            $_SESSION['flash_error'] = 'La nueva contraseña debe tener al menos 6 caracteres.';
            Security::redirect('cambiar-password');
            return;
        }

        // 3. Validar coincidencia de nueva contraseña
        if ($newPassword !== $confirmPassword) {
            $_SESSION['flash_error'] = 'La nueva contraseña y su confirmación no coinciden.';
            Security::redirect('cambiar-password');
            return;
        }

        // 4. Obtener usuario actual y verificar contraseña actual
        $currentUser = Security::getCurrentUser();
        $userId = (int)($currentUser['id_usuario'] ?? 0);

        try {
            $userDb = Usuario::findById($this->pdo, $userId);
            if ($userDb === null || empty($userDb['password_hash'])) {
                $_SESSION['flash_error'] = 'Usuario no encontrado en la base de datos.';
                Security::redirect('cambiar-password');
                return;
            }

            if (!Security::verifyPassword($currentPassword, (string)$userDb['password_hash'])) {
                $_SESSION['flash_error'] = 'La contraseña actual ingresada es incorrecta.';
                Security::redirect('cambiar-password');
                return;
            }

            // 5. Encriptar y actualizar
            $newHash = Security::hashPassword($newPassword);
            $actualizado = Usuario::updatePassword($this->pdo, $userId, $newHash);

            if ($actualizado) {
                $_SESSION['flash_success'] = '¡Tu contraseña ha sido actualizada exitosamente!';
                Security::redirect('cambiar-password');
                return;
            } else {
                $_SESSION['flash_error'] = 'No fue posible actualizar la contraseña en el sistema.';
                Security::redirect('cambiar-password');
                return;
            }
        } catch (\Throwable $e) {
            error_log('Error en cambiarPassword: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Ocurrió un error inesperado al actualizar la contraseña.';
            Security::redirect('cambiar-password');
        }
    }
}
