<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Tests\Support\RefreshDatabaseTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImportEndpointTest extends WebTestCase
{
    use RefreshDatabaseTrait;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    public function testImportsProductsAndReportsStatistics(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();

        $client->request('POST', '/api/import/products', [], [
            'file' => $this->uploadedWorkbook('external-1', 'Imported product', '1200,00'),
        ]);

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(1, $payload['created']);
        self::assertSame(0, $payload['updated']);
        self::assertSame(0, $payload['skipped']);

        $repository = static::getContainer()->get(ProductRepository::class);
        $product = $repository->findOneByExternalCode('external-1');
        self::assertInstanceOf(Product::class, $product);
        self::assertSame('50.00', $product->getDiscountPercent());
    }

    public function testRepeatedImportUpdatesByExternalCode(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();

        $client->request('POST', '/api/import/products', [], [
            'file' => $this->uploadedWorkbook('external-1', 'Imported product', '1200,00'),
        ]);
        self::assertResponseIsSuccessful();

        $client->request('POST', '/api/import/products', [], [
            'file' => $this->uploadedWorkbook('external-1', 'Updated product', '1500,00'),
        ]);
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(0, $payload['created']);
        self::assertSame(1, $payload['updated']);

        $repository = static::getContainer()->get(ProductRepository::class);
        $products = $repository->findAll();
        self::assertCount(1, $products);
        self::assertSame('Updated product', $products[0]->getName());
    }

    public function testRejectsMissingFile(): void
    {
        $client = static::createClient();
        $this->refreshDatabase();

        $client->request('POST', '/api/import/products');

        self::assertResponseStatusCodeSame(400);
    }

    private function uploadedWorkbook(string $externalCode, string $name, string $price): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Внешний код', 'Наименование', 'Цена: Цена продажи', 'Описание', 'Закупочная цена', 'Доп. поле: Размер'],
            [$externalCode, $name, $price, 'Description', '800,00', 'M'],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'import-endpoint-test-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            'import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}
