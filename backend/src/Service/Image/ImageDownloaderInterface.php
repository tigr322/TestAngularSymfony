<?php

declare(strict_types=1);

namespace App\Service\Image;

interface ImageDownloaderInterface
{
    public function download(string $url, string $externalCode): string;
}
