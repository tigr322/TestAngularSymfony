<?php

declare(strict_types=1);

namespace App\Service\Product;

use App\DTO\ProductImportRow;
use App\DTO\ProductSyncResult;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\Import\DiscountCalculator;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProductSynchronizer
{
    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private DiscountCalculator $discountCalculator,
        private AttributeSynchronizer $attributeSynchronizer,
        private ImageSynchronizer $imageSynchronizer,
    ) {
    }

    public function sync(ProductImportRow $row): ProductSyncResult
    {
        $product = $this->productRepository->findOneByExternalCode($row->externalCode);
        $created = false;

        if ($product === null) {
            $product = new Product($row->externalCode);
            $this->entityManager->persist($product);
            $created = true;
        }

        $product->setName($row->name);
        $product->setDescription($row->description);
        $product->setPrice($row->price);
        $product->setPurchasePrice($row->purchasePrice);
        $product->setDiscountPercent($this->discountCalculator->calculate($row->price, $row->purchasePrice));

        $this->attributeSynchronizer->sync($product, $row->attributes);
        $imageErrors = $this->imageSynchronizer->sync($product, $row->externalCode, $row->rowNumber, $row->imageUrls);

        return new ProductSyncResult($created, $imageErrors);
    }
}
