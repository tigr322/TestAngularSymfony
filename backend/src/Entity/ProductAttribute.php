<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductAttributeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductAttributeRepository::class)]
#[ORM\Table(name: 'product_attributes')]
#[ORM\UniqueConstraint(name: 'uniq_product_attribute_key', columns: ['product_id', 'attribute_key'])]
class ProductAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attributes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(name: 'attribute_key', length: 255)]
    private string $attributeKey;

    #[ORM\Column(name: 'attribute_value', type: 'text')]
    private string $attributeValue;

    public function __construct(string $attributeKey, string $attributeValue)
    {
        $this->attributeKey = $attributeKey;
        $this->attributeValue = $attributeValue;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getAttributeKey(): string
    {
        return $this->attributeKey;
    }

    public function getAttributeValue(): string
    {
        return $this->attributeValue;
    }

    public function setAttributeValue(string $attributeValue): void
    {
        $this->attributeValue = $attributeValue;
    }
}
