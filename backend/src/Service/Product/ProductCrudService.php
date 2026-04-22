<?php

declare(strict_types=1);

namespace App\Service\Product;

use App\DTO\ProductWriteData;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\Import\DecimalNormalizer;
use App\Service\Import\DiscountCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class ProductCrudService
{
    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private DecimalNormalizer $decimalNormalizer,
        private DiscountCalculator $discountCalculator,
        private AttributeSynchronizer $attributeSynchronizer,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): Product
    {
        $data = $this->validatedData($payload);

        if ($this->productRepository->findOneByExternalCode($data->externalCode) !== null) {
            throw new ConflictHttpException('Product with this externalCode already exists.');
        }

        $product = new Product($data->externalCode);
        $this->applyData($product, $data);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    /** @param array<string, mixed> $payload */
    public function update(Product $product, array $payload): Product
    {
        $data = $this->validatedData($payload);
        $existing = $this->productRepository->findOneByExternalCode($data->externalCode);

        if ($existing !== null && $existing->getId() !== $product->getId()) {
            throw new ConflictHttpException('Product with this externalCode already exists.');
        }

        $product->setExternalCode($data->externalCode);
        $this->applyData($product, $data);
        $this->entityManager->flush();

        return $product;
    }

    public function delete(Product $product): void
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }

    private function applyData(Product $product, ProductWriteData $data): void
    {
        $product->setName($data->name);
        $product->setDescription($data->description);
        $product->setPrice($data->price);
        $product->setPurchasePrice($data->purchasePrice);
        $product->setDiscountPercent($this->discountCalculator->calculate($data->price, $data->purchasePrice));

        $this->attributeSynchronizer->sync($product, $data->attributes);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validatedData(array $payload): ProductWriteData
    {
        $errors = [];

        $externalCode = $this->requiredString($payload, 'externalCode', 255, $errors);
        $name = $this->requiredString($payload, 'name', 500, $errors);
        $description = $this->optionalString($payload, 'description', $errors);
        $price = $this->requiredDecimal($payload, 'price', $errors);
        $purchasePrice = $this->optionalDecimal($payload, 'purchasePrice', $errors);
        $attributes = $this->attributes($payload['attributes'] ?? null, $errors);

        if ($errors !== []) {
            throw new BadRequestHttpException(implode(' ', $errors));
        }

        return new ProductWriteData(
            externalCode: $externalCode ?? '',
            name: $name ?? '',
            description: $description,
            price: $price ?? '0.00',
            purchasePrice: $purchasePrice,
            attributes: $attributes,
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $errors
     */
    private function requiredString(array $payload, string $field, int $maxLength, array &$errors): ?string
    {
        if (!array_key_exists($field, $payload)) {
            $errors[] = sprintf('Field "%s" is required.', $field);

            return null;
        }

        $value = $this->stringValue($payload[$field]);
        if ($value === null || $value === '') {
            $errors[] = sprintf('Field "%s" must be a non-empty string.', $field);

            return null;
        }

        if ($this->stringLength($value) > $maxLength) {
            $errors[] = sprintf('Field "%s" must be at most %d characters.', $field, $maxLength);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $errors
     */
    private function optionalString(array $payload, string $field, array &$errors): ?string
    {
        if (!array_key_exists($field, $payload) || $payload[$field] === null) {
            return null;
        }

        $value = $this->stringValue($payload[$field]);
        if ($value === null) {
            $errors[] = sprintf('Field "%s" must be a string or null.', $field);

            return null;
        }

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $errors
     */
    private function requiredDecimal(array $payload, string $field, array &$errors): ?string
    {
        if (!array_key_exists($field, $payload)) {
            $errors[] = sprintf('Field "%s" is required.', $field);

            return null;
        }

        return $this->decimal($payload[$field], $field, false, $errors);
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $errors
     */
    private function optionalDecimal(array $payload, string $field, array &$errors): ?string
    {
        if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
            return null;
        }

        return $this->decimal($payload[$field], $field, true, $errors);
    }

    /** @param list<string> $errors */
    private function decimal(mixed $value, string $field, bool $optional, array &$errors): ?string
    {
        if (is_array($value) || is_object($value)) {
            $errors[] = sprintf('Field "%s" must be a decimal number.', $field);

            return null;
        }

        $normalized = $this->decimalNormalizer->normalize($value);
        if ($normalized === null) {
            $errors[] = sprintf(
                'Field "%s" must be a valid decimal number%s.',
                $field,
                $optional ? ' or null' : '',
            );

            return null;
        }

        if ((float) $normalized < 0.0) {
            $errors[] = sprintf('Field "%s" must be greater than or equal to 0.', $field);
        }

        return $normalized;
    }

    /**
     * @param list<string> $errors
     * @return array<string, string>
     */
    private function attributes(mixed $value, array &$errors): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            $errors[] = 'Field "attributes" must be an object or a list of key/value objects.';

            return [];
        }

        return array_is_list($value)
            ? $this->attributesFromList($value, $errors)
            : $this->attributesFromMap($value, $errors);
    }

    /**
     * @param list<mixed> $items
     * @param list<string> $errors
     * @return array<string, string>
     */
    private function attributesFromList(array $items, array &$errors): array
    {
        $attributes = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                $errors[] = sprintf('Attribute at index %d must be an object.', $index);
                continue;
            }

            $key = $this->stringValue($item['key'] ?? null);
            $value = $this->stringValue($item['value'] ?? null);
            if ($key === null || $key === '' || $value === null || $value === '') {
                $errors[] = sprintf('Attribute at index %d must contain non-empty "key" and "value".', $index);
                continue;
            }

            $attributes[$key] = $value;
        }

        return $attributes;
    }

    /**
     * @param array<string|int, mixed> $items
     * @param list<string> $errors
     * @return array<string, string>
     */
    private function attributesFromMap(array $items, array &$errors): array
    {
        $attributes = [];

        foreach ($items as $key => $value) {
            $attributeKey = is_int($key) ? (string) $key : trim($key);
            $attributeValue = $this->stringValue($value);

            if ($attributeKey === '' || $attributeValue === null || $attributeValue === '') {
                $errors[] = 'Attribute keys and values must be non-empty strings.';
                continue;
            }

            $attributes[$attributeKey] = $attributeValue;
        }

        return $attributes;
    }

    private function stringValue(mixed $value): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        if (is_int($value) || is_float($value)) {
            return trim((string) $value);
        }

        if (is_string($value)) {
            return trim($value);
        }

        return null;
    }

    private function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}
