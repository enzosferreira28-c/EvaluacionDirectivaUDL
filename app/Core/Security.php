<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Utilidades de seguridad, gestión de sesiones, CSRF y autenticación.
 */
class Security
{
    /**
     * Inicia la sesión PHP con parámetros de cookie seguros si no está iniciada.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            session_start();
        }
    }

    /**
     * Determina si el usuario actual ha iniciado sesión.
     */
    public static function isAuthenticated(): bool
    {
        self::startSession();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Exige que el usuario esté autenticado. De lo contrario, redirige a login.
     */
    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            self::redirect('login');
        }
    }

    /**
     * Obtiene los datos del usuario autenticado actual desde la sesión.
     *
     * @return array{id: int, nombre: string, email: string}|null
     */
    public static function getCurrentUser(): ?array
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return [
            'id'     => (int)$_SESSION['user_id'],
            'nombre' => (string)($_SESSION['user_nombre'] ?? ''),
            'email'  => (string)($_SESSION['user_email'] ?? ''),
        ];
    }

    /**
     * Inicia sesión para un usuario regenerando el ID de sesión.
     */
    public static function login(int $userId, string $nombre, string $email): void
    {
        self::startSession();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_nombre'] = $nombre;
        $_SESSION['user_email'] = $email;
        $_SESSION['logged_in_at'] = time();
    }

    /**
     * Cierra la sesión activa, destruyendo variables y cookie de sesión, y redirige al login.
     */
    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        self::redirect('login');
    }

    /**
     * Genera y almacena un token CSRF seguro en la sesión si aún no existe.
     */
    public static function generateCsrfToken(): string
    {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION['csrf_token'];
    }

    /**
     * Valida si el token proporcionado coincide con el de la sesión.
     */
    public static function validateCsrfToken(?string $token): bool
    {
        self::startSession();
        if ($token === null || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals((string)$_SESSION['csrf_token'], $token);
    }

    /**
     * Retorna el campo HTML <input type="hidden"> con el token CSRF actual.
     */
    public static function csrfField(): string
    {
        $token = htmlspecialchars(self::generateCsrfToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Genera el hash seguro de una contraseña con Bcrypt.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Comprueba si una contraseña en texto plano coincide con su hash almacenado.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Redirige hacia una ruta o URL específica y finaliza la ejecución.
     */
    public static function redirect(string $route): void
    {
        if (str_starts_with($route, 'http://') || str_starts_with($route, 'https://')) {
            header('Location: ' . $route);
        } elseif (str_starts_with($route, 'index.php')) {
            header('Location: ' . $route);
        } else {
            $clean = ltrim($route, '/');
            header('Location: index.php?route=' . $clean);
        }
        exit;
    }
}
