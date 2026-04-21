<?php

declare(strict_types=1);

namespace App\Service\Product;

use App\Entity\Product;

final class ProductResponseFactory
{
    /** @return array{id: int|null, externalCode: string, name: string, price: string, purchasePrice: string|null, discountPercent: string|null, imageCount: int, firstImage: string|null} */
    public function listItem(Product $product): array
    {
        $firstImage = $product->getImages()->first();

        return [
            'id' => $product->getId(),
            'externalCode' => $product->getExternalCode(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'purchasePrice' => $product->getPurchasePrice(),
            'discountPercent' => $product->getDiscountPercent(),
            'imageCount' => $product->getImages()->count(),
            'firstImage' => $firstImage === false ? null : $firstImage->getLocalPath(),
        ];
    }

    /** @return array<string, mixed> */
    public function details(Product $product): array
    {
        $attributes = [];
        foreach ($product->getAttributes() as $attribute) {
            $attributes[] = [
                'key' => $attribute->getAttributeKey(),
                'value' => $attribute->getAttributeValue(),
            ];
        }

        $images = [];
        foreach ($product->getImages() as $image) {
            $images[] = [
                'id' => $image->getId(),
                'sourceUrl' => $image->getSourceUrl(),
                'localPath' => $image->getLocalPath(),
            ];
        }

        return [
            'id' => $product->getId(),
            'externalCode' => $product->getExternalCode(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'purchasePrice' => $product->getPurchasePrice(),
            'discountPercent' => $product->getDiscountPercent(),
            'createdAt' => $product->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $product->getUpdatedAt()->format(DATE_ATOM),
            'attributes' => $attributes,
            'images' => $images,
        ];
    }
}
