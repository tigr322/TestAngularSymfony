<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ParsedImportRows
{
    /**
     * @param list<ProductImportRow> $rows
     * @param list<ImportRowError> $errors
     */
    public function __construct(
        public array $rows,
        public array $errors,
        public int $skipped,
    ) {
    }
}
