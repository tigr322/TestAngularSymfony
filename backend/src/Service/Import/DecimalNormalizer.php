<?php

declare(strict_types=1);

namespace App\Service\Import;

final class DecimalNormalizer
{
    public function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(["\xc2\xa0", ' '], '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        if (!preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            return null;
        }

        return number_format((float) $normalized, 2, '.', '');
    }
}
