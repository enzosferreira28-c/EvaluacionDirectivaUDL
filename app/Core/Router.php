<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

class Router
{
    /**
     * @var array<string, array<string, callable|array{0: class-string, 1: string}>>
     */
    private static array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    /**
     * Registra una ruta para el método GET.
     *
     * @param string $route
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public static function get(string $route, callable|array $handler): void
    {
        $normalized = trim($route, '/');
        self::$routes['GET'][$normalized] = $handler;
    }

    /**
     * Registra una ruta para el método POST.
     *
     * @param string $route
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public static function post(string $route, callable|array $handler): void
    {
        $normalized = trim($route, '/');
        self::$routes['POST'][$normalized] = $handler;
    }

    /**
     * Despacha la solicitud actual hacia el controlador o función registrada.
     *
     * @param PDO|null $pdo Instancia opcional de conexión a la base de datos
     */
    public static function dispatch(?PDO $pdo = null): void
    {
        if ($pdo === null && function_exists('getDbConnection')) {
            $pdo = getDbConnection();
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $rawRoute = (string)($_GET['route'] ?? 'login');
        $route = trim($rawRoute, '/');
        if ($route === '') {
            $route = 'login';
        }

        $handler = self::$routes[$method][$route] ?? null;

        if ($handler === null) {
            self::renderNotFound($route);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $action] = $handler;

            if (!class_exists($class)) {
                http_response_code(500);
                self::renderError("Error 500: Controlador '{$class}' no encontrado.");
                return;
            }

            try {
                $reflector = new \ReflectionClass($class);
                $constructor = $reflector->getConstructor();

                if ($constructor !== null && $constructor->getNumberOfParameters() > 0 && $pdo !== null) {
                    $instance = new $class($pdo);
                } else {
                    $instance = new $class();
                }

                if (!method_exists($instance, $action)) {
                    http_response_code(500);
                    self::renderError("Error 500: Método '{$action}' no existe en '{$class}'.");
                    return;
                }

                $instance->$action();
            } catch (\Throwable $e) {
                http_response_code(500);
                self::renderError("Error interno al procesar la acción: " . $e->getMessage());
            }
            return;
        }

        if (is_callable($handler)) {
            try {
                $reflector = new \ReflectionFunction(\Closure::fromCallable($handler));
                if ($reflector->getNumberOfParameters() > 0 && $pdo !== null) {
                    $handler($pdo);
                } else {
                    $handler();
                }
            } catch (\Throwable $e) {
                http_response_code(500);
                self::renderError("Error interno al ejecutar la ruta: " . $e->getMessage());
            }
            return;
        }

        http_response_code(500);
        self::renderError("Controlador o manejador de ruta no válido.");
    }

    /**
     * Muestra la pantalla 404 con Tailwind CSS.
     */
    private static function renderNotFound(string $route): void
    {
        http_response_code(404);
        $escapedRoute = htmlspecialchars($route, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8 text-center">
        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Página no encontrada</h1>
        <p class="text-sm text-gray-500 mb-6">La ruta solicitada <code class="bg-gray-100 px-2 py-1 rounded text-red-600 font-mono">{$escapedRoute}</code> no existe o no se encuentra disponible.</p>
        <a href="index.php?route=login" class="inline-flex items-center justify-center w-full px-5 py-3 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition duration-150">
            Regresar al inicio
        </a>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Muestra la pantalla de error 500.
     */
    private static function renderError(string $message): void
    {
        $escaped = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error en la solicitud</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-lg w-full bg-white rounded-xl shadow-lg p-8 text-center">
        <h1 class="text-2xl font-bold text-red-600 mb-4">Ocurrió un error</h1>
        <p class="text-gray-700 mb-6">{$escaped}</p>
        <a href="index.php?route=dashboard" class="inline-block px-5 py-2.5 bg-gray-800 hover:bg-gray-900 text-white font-medium rounded-lg transition">
            Volver al Panel
        </a>
    </div>
</body>
</html>
HTML;
    }
}
