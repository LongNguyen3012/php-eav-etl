<?php

declare(strict_types=1);

namespace EavEtl;

use PDO;

/**
 * Loads flat records into a relational table using upsert.
 */
final class RelationalLoader
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $tableName
    ) {}

    /**
     * Create the target table if it doesn't exist.
     *
     * @param array<string, string> $columns   column_name => SQL type
     * @param string[]              $indexes   optional index definitions
     */
    public function createTableIfNotExists(array $columns, array $indexes = []): void
    {
        $columnDefs = [];
        foreach ($columns as $name => $type) {
            $columnDefs[] = sprintf('`%s` %s', $name, $type);
        }

        $indexDefs = array_map(fn($i) => "INDEX {$i}", $indexes);

        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS `%s` (
                %s,
                synced_at DATETIME DEFAULT CURRENT_TIMESTAMP
                %s
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            $this->tableName,
            implode(",\n", $columnDefs),
            $indexDefs ? ",\n" . implode(",\n", $indexDefs) : ''
        );

        $this->pdo->exec($sql);
    }

    /**
     * Insert or update one record.
     *
     * @param array<string, mixed> $record
     * @return 'inserted'|'updated'
     */
    public function upsert(array $record): string
    {
        $columns = array_keys($record);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $updates = [];
        foreach ($columns as $col) {
            if ($col === 'id') continue;
            $updates[] = sprintf('`%s` = VALUES(`%s`)', $col, $col);
        }
        $updates[] = 'synced_at = NOW()';
        // Trick to detect insert vs update via rowCount()
        $updates[] = '`id` = LAST_INSERT_ID(`id`)';

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)
             ON DUPLICATE KEY UPDATE %s',
            $this->tableName,
            implode('`, `', $columns),
            $placeholders,
            implode(', ', $updates)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($record));

        return $stmt->rowCount() === 1 ? 'inserted' : 'updated';
    }
}