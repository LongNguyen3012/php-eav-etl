<?php

declare(strict_types=1);

namespace EavEtl\Tests;

use EavEtl\FieldMapper;
use PHPUnit\Framework\TestCase;

final class FieldMapperTest extends TestCase
{
    public function testTransformsFullRecord(): void
    {
        $mapper = new FieldMapper(
            map: [
                'region'     => 'region',
                'unit_count' => 'unit_count',
            ],
            casters: [
                'unit_count' => fn($v) => FieldMapper::parseNumericString($v),
            ]
        );

        $eavRows = [
            ['meta_key' => 'region',     'meta_value' => 'north'],
            ['meta_key' => 'unit_count', 'meta_value' => '1,000 units'],
            ['meta_key' => '_edit_lock', 'meta_value' => '123'],
        ];

        $result = $mapper->transform(42, $eavRows);

        $this->assertSame(42, $result['id']);
        $this->assertSame('north', $result['region']);
        $this->assertSame(1000, $result['unit_count']);
        $this->assertArrayNotHasKey('_edit_lock', $result);
    }

    public function testMissingFieldsBecomeNull(): void
    {
        $mapper = new FieldMapper(map: ['region' => 'region']);

        $result = $mapper->transform(1, []);

        $this->assertNull($result['region']);
    }

    public function testEmptyStringBecomesNull(): void
    {
        $mapper = new FieldMapper(map: ['region' => 'region']);

        $result = $mapper->transform(1, [
            ['meta_key' => 'region', 'meta_value' => ''],
        ]);

        $this->assertNull($result['region']);
    }

    /**
     * @dataProvider numericStringProvider
     */
    public function testParseNumericString(?string $input, ?int $expected): void
    {
        $this->assertSame($expected, FieldMapper::parseNumericString($input));
    }

    public static function numericStringProvider(): array
    {
        return [
            'plain number'         => ['1000', 1000],
            'US format'            => ['10,946 units', 10946],
            'EU/VN format'         => ['9.673 đơn vị', 9673],
            'prefix text'          => ['Approx. 1,450 units', 1450],
            'suffix question mark' => ['Approx. 26,500?', 26500],
            'empty'                => ['', null],
            'null'                 => [null, null],
            'no digits'            => ['no digits here', null],
        ];
    }
}