<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\DTO\ImportRowError;
use App\DTO\ParsedImportRows;
use App\Exception\ImportFileException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

final readonly class XlsxProductParser
{
    private const REQUIRED_HEADERS = [
        'Внешний код',
        'Наименование',
        'Цена: Цена продажи',
        'Описание',
        'Закупочная цена',
    ];

    public function __construct(private ProductRowMapper $rowMapper)
    {
    }

    public function parse(string $path): ParsedImportRows
    {
        try {
            $spreadsheet = IOFactory::load($path);
        } catch (ReaderException|\PhpOffice\PhpSpreadsheet\Exception $exception) {
            throw new ImportFileException('The uploaded file is not a readable .xlsx document.', previous: $exception);
        }

        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestDataRow();
        $highestColumn = Coordinate::columnIndexFromString($worksheet->getHighestDataColumn());
        $headers = $this->readHeaders($worksheet, $highestColumn);
        $this->assertRequiredHeaders($headers);

        $rows = [];
        $errors = [];
        $skipped = 0;

        for ($rowNumber = 2; $rowNumber <= $highestRow; ++$rowNumber) {
            $values = $this->readRow($worksheet, $headers, $rowNumber);
            if ($this->isEmptyRow($values)) {
                continue;
            }

            $mapping = $this->rowMapper->map($rowNumber, $values);
            foreach ($mapping->errors as $error) {
                $errors[] = $error;
            }

            if ($mapping->skip) {
                ++$skipped;
                continue;
            }

            if ($mapping->row !== null) {
                $rows[] = $mapping->row;
            }
        }

        return new ParsedImportRows($rows, $errors, $skipped);
    }

    /** @return array<int, string> */
    private function readHeaders(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, int $highestColumn): array
    {
        $headers = [];

        for ($column = 1; $column <= $highestColumn; ++$column) {
            $header = trim((string) $worksheet->getCell(Coordinate::stringFromColumnIndex($column).'1')->getValue());
            if ($header !== '') {
                $headers[$column] = $header;
            }
        }

        return $headers;
    }

    /** @param array<int, string> $headers */
    private function assertRequiredHeaders(array $headers): void
    {
        $existingHeaders = array_values($headers);
        foreach (self::REQUIRED_HEADERS as $requiredHeader) {
            if (!in_array($requiredHeader, $existingHeaders, true)) {
                throw new ImportFileException(sprintf('Required column "%s" is missing.', $requiredHeader));
            }
        }
    }

    /**
     * @param array<int, string> $headers
     * @return array<string, mixed>
     */
    private function readRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, array $headers, int $rowNumber): array
    {
        $values = [];

        foreach ($headers as $column => $header) {
            $cell = $worksheet->getCell(Coordinate::stringFromColumnIndex($column).$rowNumber);
            $values[$header] = $cell->getCalculatedValue();
        }

        return $values;
    }

    /** @param array<string, mixed> $values */
    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
