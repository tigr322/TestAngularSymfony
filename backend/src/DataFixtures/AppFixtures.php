<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductImage;
use App\Service\Import\DiscountCalculator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AppFixtures extends Fixture
{
    public function __construct(private readonly DiscountCalculator $discountCalculator)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $product = new Product('fixture-external-code');
        $product->setName('Fixture imported product');
        $product->setDescription('Small realistic fixture for local development.');
        $product->setPrice('1200.00');
        $product->setPurchasePrice('800.00');
        $product->setDiscountPercent($this->discountCalculator->calculate('1200.00', '800.00'));
        $product->addAttribute(new ProductAttribute('Бренд', 'Fixture Brand'));
        $product->addAttribute(new ProductAttribute('Размер', 'M'));
        $product->addImage(new ProductImage('https://example.test/product.jpg', '/uploads/fixtures/product.jpg'));

        $manager->persist($product);

        $manager->flush();
    }
}
