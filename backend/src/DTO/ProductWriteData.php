<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ProductWriteData
{
    /** @param array<string, string> $attributes */
    public function __construct(
        public string $externalCode,
        public string $name,
        public ?string $description,
        public string $price,
        public ?string $purchasePrice,
        public array $attributes,
    ) {
    }
}
