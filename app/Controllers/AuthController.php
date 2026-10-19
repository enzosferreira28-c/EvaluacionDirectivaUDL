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
}
