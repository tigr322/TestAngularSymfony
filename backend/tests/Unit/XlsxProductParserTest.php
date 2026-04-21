<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Exception\ImportFileException;
use App\Service\Import\DecimalNormalizer;
use App\Service\Import\ProductRowMapper;
use App\Service\Import\XlsxProductParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

final class XlsxProductParserTest extends TestCase
{
    public function testParsesRowsFromXlsxFile(): void
    {
        $path = $this->createWorkbook([
            ['Внешний код', 'Наименование', 'Цена: Цена продажи', 'Описание', 'Закупочная цена', 'Доп. поле: Цвет'],
            ['external-1', 'Product name', '900,00', 'Description', '600,00', 'Nero'],
        ]);

        $parser = new XlsxProductParser(new ProductRowMapper(new DecimalNormalizer()));
        $parsed = $parser->parse($path);

        self::assertCount(1, $parsed->rows);
        self::assertSame('external-1', $parsed->rows[0]->externalCode);
        self::assertSame('Nero', $parsed->rows[0]->attributes['Цвет']);
    }

    public function testFailsWhenRequiredHeaderIsMissing(): void
    {
        $path = $this->createWorkbook([
            ['Внешний код', 'Наименование', 'Описание', 'Закупочная цена'],
            ['external-1', 'Product name', 'Description', '600,00'],
        ]);

        $parser = new XlsxProductParser(new ProductRowMapper(new DecimalNormalizer()));

        $this->expectException(ImportFileException::class);
        $parser->parse($path);
    }

    /** @param list<list<string>> $rows */
    private function createWorkbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValue([$columnIndex + 1, $rowIndex + 1], $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx-parser-test-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
