<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ProductSyncResult
{
    /** @param list<ImportRowError> $errors */
    public function __construct(
        public bool $created,
        public array $errors = [],
    ) {
    }
}
