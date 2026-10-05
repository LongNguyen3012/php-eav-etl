<?php

declare(strict_types=1);

namespace EavEtl;

/**
 * Transforms EAV rows into a flat record (object with defined columns).
 *
 * Pivots rows like:
 *   [['meta_key' => 'price', 'meta_value' => '1000'], ...]
 * Into:
 *   ['id' => 1, 'price' => 1000, ...]
 */
final class FieldMapper
{
    /**
     * @param array<string, string>          $map      source_key => target_column
     * @param array<string, callable>        $casters  target_column => cast function
     * @param array<string, string|null>     $defaults target_column => default value
     */
    public function __construct(
        private readonly array $map,
        private readonly array $casters = [],
        private readonly array $defaults = []
    ) {}

    /**
     * @param array<int, array{meta_key: string, meta_value: string}> $eavRows
     * @return array<string, mixed>
     */
    public function transform(int $entityId, array $eavRows): array
    {
        $record = ['id' => $entityId];
        foreach ($this->map as $targetColumn) {
            $record[$targetColumn] = $this->defaults[$targetColumn] ?? null;
        }

        foreach ($eavRows as $row) {
            $sourceKey = $row['meta_key'];
            $value     = $row['meta_value'];

            if (!isset($this->map[$sourceKey])) {
                continue; // whitelist — ignore unknown keys
            }

            $targetColumn = $this->map[$sourceKey];

            if (isset($this->casters[$targetColumn])) {
                $value = ($this->casters[$targetColumn])($value);
            } elseif ($value === '') {
                $value = null;
            }

            $record[$targetColumn] = $value;
        }

        return $record;
    }

    /**
     * Helper: parse numbers like "10,946 units", "Approx. 1,450", "9.673 đơn vị"
     * Returns null if no number found.
     */
    public static function parseNumericString(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!preg_match('/(\d[\d,\.]*)/', $value, $m)) {
            return null;
        }

        $numStr = $m[1];

        // Handle both US (1,234.56) and EU/VN (1.234,56) thousand separators
        if (str_contains($numStr, ',') && str_contains($numStr, '.')) {
            $lastComma = strrpos($numStr, ',');
            $lastDot   = strrpos($numStr, '.');
            $numStr = $lastComma > $lastDot
                ? str_replace(['.', ','], ['', '.'], $numStr)  // EU/VN format
                : str_replace(',', '', $numStr);                // US format
        } else {
            $numStr = str_replace([',', '.'], '', $numStr);
        }

        $num = (int) $numStr;
        return $num > 0 ? $num : null;
    }
}