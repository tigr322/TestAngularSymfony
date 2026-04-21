<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ProductImportRow
{
    /**
     * @param array<string, string> $attributes
     * @param list<string> $imageUrls
     */
    public function __construct(
        public int $rowNumber,
        public string $externalCode,
        public string $name,
        public ?string $description,
        public string $price,
        public ?string $purchasePrice,
        public array $attributes,
        public array $imageUrls,
    ) {
    }
}
