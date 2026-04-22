<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductImage;
use App\Tests\Support\RefreshDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductApiTest extends WebTestCase
{
    use RefreshDatabaseTrait;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    public function testListsProducts(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();
        $this->persistProduct();

        $client->request('GET', '/api/products');

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertArrayHasKey('items', $payload);
        self::assertIsArray($payload['items']);
        self::assertCount(1, $payload['items']);
        self::assertArrayHasKey(0, $payload['items']);
        self::assertIsArray($payload['items'][0]);
        self::assertSame('fixture-code', $payload['items'][0]['externalCode']);
    }

    public function testShowsProductDetails(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();
        $product = $this->persistProduct();

        $client->request('GET', '/api/products/'.$product->getId());

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertSame('Fixture product', $payload['name']);
        self::assertArrayHasKey('attributes', $payload);
        self::assertIsArray($payload['attributes']);
        self::assertArrayHasKey(0, $payload['attributes']);
        self::assertIsArray($payload['attributes'][0]);
        self::assertSame('Бренд', $payload['attributes'][0]['key']);
        self::assertArrayHasKey('images', $payload);
        self::assertIsArray($payload['images']);
        self::assertArrayHasKey(0, $payload['images']);
        self::assertIsArray($payload['images'][0]);
        self::assertSame('/uploads/fixture.jpg', $payload['images'][0]['localPath']);
    }

    public function testReturnsNotFoundForUnknownProduct(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();

        $client->request('GET', '/api/products/999999');

        self::assertResponseStatusCodeSame(404);
    }

    private function persistProduct(): Product
    {
        $product = new Product('fixture-code');
        $product->setName('Fixture product');
        $product->setDescription('Fixture description');
        $product->setPrice('100.00');
        $product->setPurchasePrice('80.00');
        $product->setDiscountPercent('25.00');
        $product->addAttribute(new ProductAttribute('Бренд', 'Fixture'));
        $product->addImage(new ProductImage('http://example.test/fixture.jpg', '/uploads/fixture.jpg'));

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }
}
