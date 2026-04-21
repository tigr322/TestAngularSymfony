<?php

declare(strict_types=1);

namespace App\Service\Import;

final class DiscountCalculator
{
    public function calculate(?string $price, ?string $purchasePrice): ?string
    {
        if ($price === null || $purchasePrice === null) {
            return null;
        }

        $purchase = (float) $purchasePrice;
        if ($purchase <= 0.0) {
            return null;
        }

        $discount = (((float) $price - $purchase) / $purchase) * 100;

        return number_format(round($discount, 2), 2, '.', '');
    }
}
