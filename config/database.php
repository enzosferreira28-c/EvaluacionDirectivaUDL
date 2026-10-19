<?php

declare(strict_types=1);

/**
 * Conexión a la base de datos MySQL mediante PDO (Patrón Singleton).
 */

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

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $instance = new PDO($dsn, $user, $pass, $options);
        return $instance;
    } catch (PDOException $e) {
        error_log("Error crítico de conexión a base de datos: " . $e->getMessage());
        http_response_code(500);
        die("Ocurrió un error al conectar con el servidor de base de datos. Por favor, contacte a soporte técnico.");
    }
}