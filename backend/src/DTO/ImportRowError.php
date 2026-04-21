<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ImportRowError
{
    public function __construct(
        public int $row,
        public string $message,
    ) {
    }

    /** @return array{row: int, message: string} */
    public function toArray(): array
    {
        return [
            'row' => $this->row,
            'message' => $this->message,
        ];
    }
}
