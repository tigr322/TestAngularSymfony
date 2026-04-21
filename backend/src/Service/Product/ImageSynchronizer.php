<?php

declare(strict_types=1);

namespace App\Service\Product;

use App\DTO\ImportRowError;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Exception\ImageDownloadException;
use App\Service\Image\ImageDownloaderInterface;

final readonly class ImageSynchronizer
{
    public function __construct(private ImageDownloaderInterface $imageDownloader)
    {
    }

    /**
     * @param list<string> $sourceUrls
     * @return list<ImportRowError>
     */
    public function sync(Product $product, string $externalCode, int $rowNumber, array $sourceUrls): array
    {
        $wantedUrls = array_fill_keys($sourceUrls, true);
        $existingByUrl = [];

        foreach ($product->getImages() as $image) {
            $existingByUrl[$image->getSourceUrl()] = $image;
        }

        foreach ($sourceUrls as $sourceUrl) {
            if (isset($existingByUrl[$sourceUrl])) {
                unset($existingByUrl[$sourceUrl]);
                continue;
            }

            try {
                $localPath = $this->imageDownloader->download($sourceUrl, $externalCode);
                $product->addImage(new ProductImage($sourceUrl, $localPath));
            } catch (ImageDownloadException $exception) {
                $errors[] = new ImportRowError($rowNumber, $exception->getMessage());
            }
        }

        foreach ($existingByUrl as $sourceUrl => $staleImage) {
            if (!isset($wantedUrls[$sourceUrl])) {
                $product->removeImage($staleImage);
            }
        }

        return $errors ?? [];
    }
}
