<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Modelo para la gestión de usuarios del sistema.
 */
class Usuario
{
    /**
     * Busca un usuario por su correo electrónico.
     *
     * @return array<string, mixed>|null
     */
    public static function findByEmail(PDO $pdo, string $email): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT id_usuario, nombre, email, password_hash, creado_en 
             FROM usuarios 
             WHERE email = :email 
             LIMIT 1'
        );
        $stmt->execute([':email' => trim($email)]);
        $user = $stmt->fetch();

        return $user !== false ? $user : null;
    }

    /**
     * Busca un usuario por su ID de clave primaria.
     *
     * @return array<string, mixed>|null
     */
    public static function findById(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT id_usuario, nombre, email, password_hash, creado_en 
             FROM usuarios 
             WHERE id_usuario = :id 
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        return $user !== false ? $user : null;
    }

    /**
     * Crea un nuevo usuario en la base de datos y retorna su ID asignado.
     */
    public static function create(PDO $pdo, string $nombre, string $email, string $passwordHash): int
    {
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nombre, email, password_hash) 
             VALUES (:nombre, :email, :password_hash)'
        );
        $stmt->execute([
            ':nombre'        => trim($nombre),
            ':email'         => trim($email),
            ':password_hash' => $passwordHash,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Actualiza la contraseña de un usuario en la base de datos.
     */
    public static function updatePassword(PDO $pdo, int $id, string $passwordHash): bool
    {
        $stmt = $pdo->prepare(
            'UPDATE usuarios 
             SET password_hash = :password_hash 
             WHERE id_usuario = :id'
        );

        return $stmt->execute([
            ':password_hash' => $passwordHash,
            ':id'            => $id,
        ]);
    }

    /**
     * Obtiene el listado completo de usuarios registrados.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(PDO $pdo): array
    {
        $stmt = $pdo->query(
            'SELECT id_usuario, nombre, email, creado_en 
             FROM usuarios 
             ORDER BY id_usuario ASC'
        );

        return $stmt->fetchAll();
    }
}
