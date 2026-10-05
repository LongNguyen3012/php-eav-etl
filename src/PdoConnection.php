<?php

declare(strict_types=1);

namespace EavEtl;

use PDO;

/**
 * PDO wrapper with production-grade defaults.
 */
final class PdoConnection
{
    private PDO $pdo;

    public function __construct(
        string $host,
        int $port,
        string $database,
        string $user,
        string $password,
        string $charset = 'utf8mb4'
    ) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        );

        $this->pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Real prepared statements — safer and faster
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
