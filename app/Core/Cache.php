<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sistema de almacenamiento en caché ligero basado en archivos.
 * Almacena datos serializados con expiración TTL en storage/cache/.
 */
class Cache
{
    private static string $cacheDir = '';

    private static function init(): void
    {
        if (self::$cacheDir === '') {
            self::$cacheDir = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2)) . '/storage/cache';
            if (!is_dir(self::$cacheDir)) {
                @mkdir(self::$cacheDir, 0755, true);
            }
        }
    }

    private static function getFilePath(string $key): string
    {
        self::init();
        $hash = md5($key);
        return self::$cacheDir . "/cache_{$hash}.dat";
    }

    /**
     * Guarda un valor en la caché con tiempo de vida (TTL) en segundos.
     * Default TTL: 3600 segundos (1 hora).
     */
    public static function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $filePath = self::getFilePath($key);
        $data = [
            'expires_at' => time() + $ttl,
            'data'       => $value
        ];

        return (bool)@file_put_contents($filePath, serialize($data), LOCK_EX);
    }

    /**
     * Obtiene un valor desde la caché. Retorna $default si no existe o ya expiró.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $filePath = self::getFilePath($key);
        if (!file_exists($filePath)) {
            return $default;
        }

        $raw = @file_get_contents($filePath);
        if ($raw === false) {
            return $default;
        }

        try {
            $data = unserialize($raw);
            if (!is_array($data) || !isset($data['expires_at'], $data['data'])) {
                self::delete($key);
                return $default;
            }

            if (time() > $data['expires_at']) {
                self::delete($key);
                return $default;
            }

            return $data['data'];
        } catch (\Throwable $e) {
            self::delete($key);
            return $default;
        }
    }

    /**
     * Elimina un elemento de la caché.
     */
    public static function delete(string $key): bool
    {
        $filePath = self::getFilePath($key);
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        return true;
    }

    /**
     * Limpia todos los archivos de caché.
     */
    public static function clear(): bool
    {
        self::init();
        $files = glob(self::$cacheDir . '/cache_*.dat');
        if ($files === false) {
            return true;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        return true;
    }

    /**
     * Función remember: si existe en caché lo retorna, sino ejecuta el callback y lo guarda.
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        self::set($key, $value, $ttl);
        return $value;
    }
}
