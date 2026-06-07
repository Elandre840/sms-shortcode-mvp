<?php

function getDb(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require dirname(__DIR__) . '/config.php';
    $db = $config['database'] ?? [];

    $pdo = new PDO(
        'mysql:host=' . ($db['host'] ?? 'localhost') . ';dbname=' . ($db['dbname'] ?? '') . ';charset=utf8mb4',
        $db['username'] ?? 'root',
        $db['password'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    return $pdo;
}

function findTechnicianByEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, email, `password` AS password_hash, full_name, category
         FROM technicians
         WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))
         LIMIT 1'
    );
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function verifyTechnicianPassword(string $password, string $storedHash, PDO $pdo, int $technicianId): bool
{
    $storedHash = trim($storedHash);

    if ($storedHash === '') {
        return false;
    }

    if (
        strncmp($storedHash, '$2y$', 4) === 0
        || strncmp($storedHash, '$2a$', 4) === 0
        || strncmp($storedHash, '$argon2', 7) === 0
    ) {
        return password_verify($password, $storedHash);
    }

    // Legacy plain-text row: allow once, then upgrade to a hash.
    if (hash_equals($storedHash, $password)) {
        $update = $pdo->prepare('UPDATE technicians SET `password` = ? WHERE id = ?');
        $update->execute([password_hash($password, PASSWORD_DEFAULT), $technicianId]);
        return true;
    }

    return false;
}
