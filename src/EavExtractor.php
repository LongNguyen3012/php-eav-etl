<?php

declare(strict_types=1);

namespace EavEtl;

use PDO;

/**
 * Extracts EAV data from WordPress-style tables.
 * Read-only — never writes to source tables.
 */
final class EavExtractor
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $entityTable = 'wp_posts',
        private readonly string $metaTable = 'wp_postmeta',
        private readonly string $entityIdColumn = 'ID',
        private readonly string $entityTypeColumn = 'post_type',
        private readonly string $entityStatusColumn = 'post_status'
    ) {}

    /**
     * Get all entity IDs of a given type.
     *
     * @return int[]
     */
    public function getEntityIds(
        string $entityType,
        string $status = 'publish',
        ?string $languageFilter = null
    ): array {
        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s = ? AND %s = ?',
            $this->entityIdColumn,
            $this->entityTable,
            $this->entityTypeColumn,
            $this->entityStatusColumn
        );
        $params = [$entityType, $status];

        if ($languageFilter !== null) {
            $sql .= ' AND ' . $this->entityIdColumn . ' IN (
                SELECT tr.object_id
                FROM wp_term_relationships tr
                JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                JOIN wp_terms t ON t.term_id = tt.term_id
                WHERE tt.taxonomy = \'language\' AND t.slug = ?
            )';
            $params[] = $languageFilter;
        }

        $sql .= ' ORDER BY ' . $this->entityIdColumn;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_column($stmt->fetchAll(), $this->entityIdColumn);
    }

    /**
     * Get all EAV metadata for one entity.
     *
     * @return array<int, array{meta_key: string, meta_value: string}>
     */
    public function getMetadata(int $entityId): array
    {
        $sql = sprintf(
            'SELECT meta_key, meta_value FROM %s WHERE post_id = ? ORDER BY meta_key',
            $this->metaTable
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$entityId]);

        return $stmt->fetchAll();
    }
}