<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductImageRepository::class)]
#[ORM\Table(name: 'product_images')]
#[ORM\UniqueConstraint(name: 'uniq_product_image_source_url', columns: ['product_id', 'source_url'])]
class ProductImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(name: 'source_url', length: 2048)]
    private string $sourceUrl;

    #[ORM\Column(name: 'local_path', length: 255)]
    private string $localPath;

    public function __construct(string $sourceUrl, string $localPath)
    {
        $this->sourceUrl = $sourceUrl;
        $this->localPath = $localPath;
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

    public function getSourceUrl(): string
    {
        return $this->sourceUrl;
    }

    public function getLocalPath(): string
    {
        return $this->localPath;
    }
}
