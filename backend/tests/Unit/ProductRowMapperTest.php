<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\Import\DecimalNormalizer;
use App\Service\Import\ProductRowMapper;
use PHPUnit\Framework\TestCase;

final class ProductRowMapperTest extends TestCase
{
    public function testMapsCoreFieldsAttributesAndImageUrls(): void
    {
        $mapper = new ProductRowMapper(new DecimalNormalizer());

        $result = $mapper->map(2, [
            'Внешний код' => 'external-1',
            'Наименование' => 'Product name',
            'Описание' => 'Description',
            'Цена: Цена продажи' => '1320,00',
            'Закупочная цена' => '880,00',
            'Доп. поле: Размер' => 'M',
            'Доп. поле: Ссылка на упаковку' => 'http://example.test/pack.jpg',
            'Доп. поле: Ссылки на фото' => 'http://example.test/1.jpg, http://example.test/2.jpg',
        ]);

        self::assertFalse($result->skip);
        self::assertNotNull($result->row);
        self::assertSame('1320.00', $result->row->price);
        self::assertSame('880.00', $result->row->purchasePrice);
        self::assertSame('M', $result->row->attributes['Размер']);
        self::assertSame([
            'http://example.test/pack.jpg',
            'http://example.test/1.jpg',
            'http://example.test/2.jpg',
        ], $result->row->imageUrls);
    }

    public function testSkipsRowsWithMissingRequiredFields(): void
    {
        $mapper = new ProductRowMapper(new DecimalNormalizer());

        $result = $mapper->map(4, [
            'Внешний код' => '',
            'Наименование' => 'Product name',
            'Цена: Цена продажи' => 'not-a-number',
        ]);

        self::assertTrue($result->skip);
        self::assertNull($result->row);
        self::assertCount(2, $result->errors);
    }
}
