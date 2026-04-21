<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\DTO\ImportRowError;
use App\DTO\ProductImportRow;
use App\DTO\ProductRowMappingResult;

final readonly class ProductRowMapper
{
    private const ATTRIBUTE_PREFIX = 'Доп. поле:';
    private const PACKAGING_IMAGE_KEY = 'Ссылка на упаковку';
    private const PRODUCT_IMAGES_KEY = 'Ссылки на фото';

    public function __construct(private DecimalNormalizer $decimalNormalizer)
    {
    }

    /** @param array<string, mixed> $values */
    public function map(int $rowNumber, array $values): ProductRowMappingResult
    {
        $errors = [];

        $externalCode = $this->stringValue($values['Внешний код'] ?? null);
        $name = $this->stringValue($values['Наименование'] ?? null);
        $description = $this->nullableStringValue($values['Описание'] ?? null);
        $price = $this->decimalNormalizer->normalize($values['Цена: Цена продажи'] ?? null);
        $purchasePrice = $this->decimalNormalizer->normalize($values['Закупочная цена'] ?? null);

        if ($externalCode === '') {
            $errors[] = new ImportRowError($rowNumber, 'Missing required field "Внешний код".');
        }

        if ($name === '') {
            $errors[] = new ImportRowError($rowNumber, 'Missing required field "Наименование".');
        }

        if ($price === null) {
            $errors[] = new ImportRowError($rowNumber, 'Missing or invalid required field "Цена: Цена продажи".');
        }

        $attributes = $this->extractAttributes($values);
        $imageUrls = $this->extractImageUrls($rowNumber, $attributes, $errors);

        if ($externalCode === '' || $name === '' || $price === null) {
            return new ProductRowMappingResult(null, $errors, true);
        }

        return new ProductRowMappingResult(
            new ProductImportRow(
                rowNumber: $rowNumber,
                externalCode: $externalCode,
                name: $name,
                description: $description,
                price: $price,
                purchasePrice: $purchasePrice,
                attributes: $attributes,
                imageUrls: $imageUrls,
            ),
            $errors,
            false,
        );
    }

    /** @param array<string, mixed> $values */
    private function extractAttributes(array $values): array
    {
        $attributes = [];

        foreach ($values as $header => $value) {
            if (!str_starts_with($header, self::ATTRIBUTE_PREFIX)) {
                continue;
            }

            $attributeKey = trim(substr($header, strlen(self::ATTRIBUTE_PREFIX)));
            $attributeValue = $this->nullableStringValue($value);

            if ($attributeKey !== '' && $attributeValue !== null) {
                $attributes[$attributeKey] = $attributeValue;
            }
        }

        return $attributes;
    }

    /**
     * @param array<string, string> $attributes
     * @param list<ImportRowError> $errors
     * @return list<string>
     */
    private function extractImageUrls(int $rowNumber, array $attributes, array &$errors): array
    {
        $candidates = [];

        if (isset($attributes[self::PACKAGING_IMAGE_KEY])) {
            $candidates[] = $attributes[self::PACKAGING_IMAGE_KEY];
        }

        if (isset($attributes[self::PRODUCT_IMAGES_KEY])) {
            foreach (explode(',', $attributes[self::PRODUCT_IMAGES_KEY]) as $url) {
                $candidates[] = $url;
            }
        }

        $urls = [];
        foreach ($candidates as $candidate) {
            $url = trim($candidate);
            if ($url === '') {
                continue;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[] = new ImportRowError($rowNumber, sprintf('Invalid image URL "%s".', $url));
                continue;
            }

            $urls[$url] = $url;
        }

        return array_values($urls);
    }

    private function nullableStringValue(mixed $value): ?string
    {
        $string = $this->stringValue($value);

        return $string === '' ? null : $string;
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return trim((string) $value);
    }
}
