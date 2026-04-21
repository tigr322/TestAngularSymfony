<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ProductRowMappingResult
{
    /** @param list<ImportRowError> $errors */
    public function __construct(
        public ?ProductImportRow $row,
        public array $errors,
        public bool $skip,
    ) {
    }
}
