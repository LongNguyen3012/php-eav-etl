<?php

/**
 * Example: Sync a WordPress post type's metadata into a relational table.
 *
 * Run: php examples/wordpress-basic.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use EavEtl\PdoConnection;
use EavEtl\EavExtractor;
use EavEtl\FieldMapper;
use EavEtl\RelationalLoader;

// 1. Connect to WordPress database
$db = new PdoConnection(
    host: 'localhost',
    port: 3306,
    database: 'wordpress',
    user: 'root',
    password: 'password'
);

// 2. Define the mapping (source EAV key → target column)
$mapper = new FieldMapper(
    map: [
        'region'      => 'region',
        'year_built'  => 'year',
        'unit_count'  => 'unit_count',
        'type'        => 'type',
    ],
    casters: [
        'year'       => fn($v) => is_numeric($v) ? (int) $v : null,
        'unit_count' => fn($v) => FieldMapper::parseNumericString($v),
    ]
);

$extractor = new EavExtractor($db->pdo());
$loader    = new RelationalLoader($db->pdo(), 'etl_records');

// 3. Create target table
$loader->createTableIfNotExists(
    columns: [
        'id'         => 'BIGINT PRIMARY KEY',
        'region'     => 'VARCHAR(20)',
        'year'       => 'INT',
        'unit_count' => 'INT',
        'type'       => 'VARCHAR(100)',
    ],
    indexes: [
        'idx_region (region)',
        'idx_year (year)',
    ]
);

// 4. Extract → Transform → Load
$ids = $extractor->getEntityIds('my_custom_type');

echo "Found " . count($ids) . " records\n";

$inserted = $updated = 0;

foreach ($ids as $id) {
    $eavRows = $extractor->getMetadata($id);
    $record  = $mapper->transform($id, $eavRows);

    $result = $loader->upsert($record);
    $result === 'inserted' ? $inserted++ : $updated++;
}

echo "Inserted: {$inserted}\n";
echo "Updated:  {$updated}\n";
