<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Exception\ImageDownloadException;
use App\Service\Image\ImageDownloaderInterface;
use App\Service\Product\ImageSynchronizer;
use PHPUnit\Framework\TestCase;

final class ImageSynchronizerTest extends TestCase
{
    public function testAddsNewImagesAndRemovesStaleImages(): void
    {
        $product = new Product('external-1');
        $product->addImage(new ProductImage('http://example.test/stale.jpg', '/uploads/stale.jpg'));

        $synchronizer = new ImageSynchronizer(new FakeImageDownloader());
        $errors = $synchronizer->sync($product, 'external-1', 2, ['http://example.test/new.jpg']);

        self::assertSame([], $errors);
        self::assertCount(1, $product->getImages());
        $firstImage = $product->getImages()->first();
        self::assertNotFalse($firstImage);
        self::assertSame('http://example.test/new.jpg', $firstImage->getSourceUrl());
    }

    public function testReportsDownloadErrorsWithoutFailingSync(): void
    {
        $product = new Product('external-1');
        $synchronizer = new ImageSynchronizer(new FailingImageDownloader());

        $errors = $synchronizer->sync($product, 'external-1', 2, ['http://example.test/new.jpg']);

        self::assertCount(1, $errors);
        self::assertCount(0, $product->getImages());
    }
}

final class FakeImageDownloader implements ImageDownloaderInterface
{
    public function download(string $url, string $externalCode): string
    {
        return '/uploads/products/'.$externalCode.'/'.sha1($url).'.jpg';
    }
}

final class FailingImageDownloader implements ImageDownloaderInterface
{
    public function download(string $url, string $externalCode): string
    {
        throw new ImageDownloadException('Download failed.');
    }
}
