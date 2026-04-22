<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Repository\ProductRepository;
use App\Tests\Support\RefreshDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductCrudApiTest extends WebTestCase
{
    use RefreshDatabaseTrait;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    public function testCreatesProductSuccessfully(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();

        $this->jsonRequest($client, 'POST', '/api/products', [
            'externalCode' => 'crud-1',
            'name' => 'CRUD product',
            'description' => 'Created through API',
            'price' => '1200,00',
            'purchasePrice' => '800.00',
            'attributes' => [
                'Бренд' => 'MINIMI',
                'Размер' => 'M',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        $payload = $this->jsonResponse($client);
        self::assertSame('crud-1', $payload['externalCode']);
        self::assertSame('CRUD product', $payload['name']);
        self::assertSame('1200.00', $payload['price']);
        self::assertSame('50.00', $payload['discountPercent']);
        self::assertArrayHasKey('attributes', $payload);
        self::assertIsArray($payload['attributes']);
        self::assertArrayHasKey(0, $payload['attributes']);
        self::assertIsArray($payload['attributes'][0]);
        self::assertSame('Бренд', $payload['attributes'][0]['key']);

        $repository = static::getContainer()->get(ProductRepository::class);
        self::assertInstanceOf(Product::class, $repository->findOneByExternalCode('crud-1'));
    }

    public function testCreateProductValidationFailure(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();

        $this->jsonRequest($client, 'POST', '/api/products', [
            'externalCode' => 'crud-invalid',
            'price' => '100.00',
        ]);

        self::assertResponseStatusCodeSame(400);
        $payload = $this->jsonResponse($client);
        self::assertArrayHasKey('error', $payload);
        self::assertIsArray($payload['error']);
        self::assertArrayHasKey('message', $payload['error']);
        self::assertIsString($payload['error']['message']);
        self::assertStringContainsString('name', $payload['error']['message']);
    }

    public function testPreventsDuplicateExternalCode(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();
        $this->persistProduct('duplicate-code');

        $this->jsonRequest($client, 'POST', '/api/products', [
            'externalCode' => 'duplicate-code',
            'name' => 'Duplicate product',
            'price' => '100.00',
        ]);

        self::assertResponseStatusCodeSame(409);
    }

    public function testReadsExistingProduct(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();
        $product = $this->persistProduct('read-code');

        $client->request('GET', '/api/products/'.$product->getId());

        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse($client);
        self::assertSame('read-code', $payload['externalCode']);
        self::assertSame('Fixture product', $payload['name']);
    }

    public function testReturnsNotFoundForMissingProductRead(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();

        $client->request('GET', '/api/products/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testUpdatesExistingProductSuccessfully(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();
        $product = $this->persistProduct('update-code');

        $this->jsonRequest($client, 'PUT', '/api/products/'.$product->getId(), [
            'externalCode' => 'updated-code',
            'name' => 'Updated product',
            'description' => null,
            'price' => '1500.00',
            'purchasePrice' => '1000.00',
            'attributes' => [
                'Бренд' => 'Updated brand',
            ],
        ]);

        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse($client);
        self::assertSame('updated-code', $payload['externalCode']);
        self::assertSame('Updated product', $payload['name']);
        self::assertSame('50.00', $payload['discountPercent']);
        self::assertArrayHasKey('attributes', $payload);
        self::assertIsArray($payload['attributes']);
        self::assertCount(1, $payload['attributes']);
        self::assertArrayHasKey(0, $payload['attributes']);
        self::assertIsArray($payload['attributes'][0]);
        self::assertSame('Updated brand', $payload['attributes'][0]['value']);
    }

    public function testUpdateValidationFailure(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();
        $product = $this->persistProduct('invalid-update-code');

        $this->jsonRequest($client, 'PUT', '/api/products/'.$product->getId(), [
            'externalCode' => 'invalid-update-code',
            'name' => 'Invalid update',
            'price' => 'not-a-price',
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    public function testDeletesExistingProductSuccessfully(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();
        $product = $this->persistProduct('delete-code');
        $id = $product->getId();

        $client->request('DELETE', '/api/products/'.$id);

        self::assertResponseStatusCodeSame(204);

        $this->entityManager->clear();
        self::assertNull(static::getContainer()->get(ProductRepository::class)->find($id));
    }

    public function testReturnsNotFoundWhenDeletingMissingProduct(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();

        $client->request('DELETE', '/api/products/999999');

        self::assertResponseStatusCodeSame(404);
    }

    /** @param array<string, mixed> $payload */
    private function jsonRequest(KernelBrowser $client, string $method, string $uri, array $payload): void
    {
        $client->request(
            $method,
            $uri,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonResponse(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }

    private function persistProduct(string $externalCode): Product
    {
        $product = new Product($externalCode);
        $product->setName('Fixture product');
        $product->setDescription('Fixture description');
        $product->setPrice('100.00');
        $product->setPurchasePrice('80.00');
        $product->setDiscountPercent('25.00');
        $product->addAttribute(new ProductAttribute('Бренд', 'Fixture'));

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }
}
