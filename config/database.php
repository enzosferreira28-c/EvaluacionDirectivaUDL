<?php

declare(strict_types=1);

/**
 * Conexión a la base de datos MySQL mediante PDO (Patrón Singleton).
 * Soporta conexiones tanto locales (127.0.0.1 / localhost) como de producción en Hostinger.
 */

use App\Core\Logger;

function getDbConnection(): PDO
{
    /** @var PDO|null $instance */
    static $instance = null;

    if ($instance !== null) {
        return $instance;
    }

    $host = (string)($_ENV['DB_HOST'] ?? (function_exists('env') ? env('DB_HOST', '127.0.0.1') : '127.0.0.1'));
    $port = (string)($_ENV['DB_PORT'] ?? (function_exists('env') ? env('DB_PORT', '3306') : '3306'));
    $dbname = (string)($_ENV['DB_NAME'] ?? (function_exists('env') ? env('DB_NAME', '') : ''));
    $user = (string)($_ENV['DB_USER'] ?? (function_exists('env') ? env('DB_USER', '') : ''));
    $pass = (string)($_ENV['DB_PASS'] ?? (function_exists('env') ? env('DB_PASS', '') : ''));

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $hostsToTry = [$host];
    // Si se especifica localhost o 127.0.0.1, agregamos el recíproco como respaldo
    if ($host === 'localhost') {
        $hostsToTry[] = '127.0.0.1';
    } elseif ($host === '127.0.0.1') {
        $hostsToTry[] = 'localhost';
    }

    $lastException = null;

    foreach ($hostsToTry as $currentHost) {
        try {
            $dsn = "mysql:host={$currentHost};port={$port};dbname={$dbname};charset=utf8mb4";
            $instance = new PDO($dsn, $user, $pass, $options);
            return $instance;
        } catch (PDOException $e) {
            $lastException = $e;
        }
    }

    // Registro de error en el sistema de logs
    if (class_exists(Logger::class)) {
        Logger::error("Error crítico de conexión a base de datos: " . ($lastException ? $lastException->getMessage() : 'Error desconocido'), [
            'host' => $host,
            'port' => $port,
            'dbname' => $dbname,
            'user' => $user
        ], 'database');
    } else {
        error_log("Error crítico de conexión a base de datos: " . ($lastException ? $lastException->getMessage() : ''));
    }

    http_response_code(500);
    die("Ocurrió un error al conectar con el servidor de base de datos. Por favor, verifique las credenciales en su archivo .env o contacte a soporte técnico.");
}
