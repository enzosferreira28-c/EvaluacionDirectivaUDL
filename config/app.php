<?php

declare(strict_types=1);

/**
 * Bootstrap y configuración global de la aplicación.
 */

// 1. Definir APP_ROOT hacia la raíz de evaluaciondirectiva/
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// 2. Cargar autoloader de Composer si existe
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

// Registro de autoloader PSR-4 para el namespace App\
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// 3. Cargar .env usando vlucas/phpdotenv si está disponible, o mediante fallback
if (class_exists(\Dotenv\Dotenv::class) && file_exists(APP_ROOT . '/.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(APP_ROOT);
    $dotenv->safeLoad();
} elseif (file_exists(APP_ROOT . '/.env')) {
    $envLines = file(APP_ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($envLines !== false) {
        foreach ($envLines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $value = trim($value, "\"'");
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    putenv("{$key}={$value}");
                }
            }
        }
    }
}

// 4. Función auxiliar env() para obtener variables de entorno
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string)$value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }
}

// 5. Función auxiliar url() para rutas query-parameter
if (!function_exists('url')) {
    function url(string $route = ''): string
    {
        $cleanRoute = trim($route, '/');
        if ($cleanRoute === '') {
            return 'index.php';
        }
        return 'index.php?route=' . $cleanRoute;
    }
}

// 6. Función auxiliar asset() para recursos estáticos
if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return 'assets/' . ltrim($path, '/');
    }
}

// 7. Constante APP_DEBUG desde el entorno
if (!defined('APP_DEBUG')) {
    $debug = env('APP_DEBUG', false);
    define('APP_DEBUG', filter_var($debug, FILTER_VALIDATE_BOOLEAN));
}

// 8. Configuración de reporte de errores según APP_DEBUG
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

// 9. Configuración de límites de PHP
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');
// Nota: 'upload_max_filesize' y 'post_max_size' deben ajustarse en php.ini o .htaccess
