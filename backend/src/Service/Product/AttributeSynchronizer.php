<?php

declare(strict_types=1);

namespace App\Service\Product;

use App\Entity\Product;
use App\Entity\ProductAttribute;

final class AttributeSynchronizer
{
    /** @param array<string, string> $attributes */
    public function sync(Product $product, array $attributes): void
    {
        $existingByKey = [];

        foreach ($product->getAttributes() as $attribute) {
            $existingByKey[$attribute->getAttributeKey()] = $attribute;
        }

        foreach ($attributes as $key => $value) {
            if (isset($existingByKey[$key])) {
                $existingByKey[$key]->setAttributeValue($value);
                unset($existingByKey[$key]);
                continue;
            }

            $product->addAttribute(new ProductAttribute($key, $value));
        }

        foreach ($existingByKey as $staleAttribute) {
            $product->removeAttribute($staleAttribute);
        }
    }
}
