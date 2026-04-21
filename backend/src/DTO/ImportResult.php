<?php

declare(strict_types=1);

namespace App\DTO;

final class ImportResult
{
    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;

    /** @var list<ImportRowError> */
    private array $errors = [];

    public function incrementCreated(): void
    {
        ++$this->created;
    }

    public function incrementUpdated(): void
    {
        ++$this->updated;
    }

    public function incrementSkipped(): void
    {
        ++$this->skipped;
    }

    public function addError(ImportRowError $error): void
    {
        $this->errors[] = $error;
    }

    /** @return array{created: int, updated: int, skipped: int, errorCount: int, errors: list<array{row: int, message: string}>} */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errorCount' => count($this->errors),
            'errors' => array_map(
                static fn (ImportRowError $error): array => $error->toArray(),
                $this->errors,
            ),
        ];
    }
}
