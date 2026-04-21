<?php

declare(strict_types=1);

namespace App\Service\Image;

use App\Exception\ImageDownloadException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class HttpImageDownloader implements ImageDownloaderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $projectDir,
    ) {
    }

    public function download(string $url, string $externalCode): string
    {
        $urlHash = sha1($url);
        $extension = $this->extensionFromUrl($url);
        $relativePath = sprintf('/uploads/products/%s/%s.%s', substr($urlHash, 0, 2), $urlHash, $extension);
        $absolutePath = $this->projectDir.'/public'.$relativePath;

        if (is_file($absolutePath)) {
            return $relativePath;
        }

        try {
            $response = $this->httpClient->request('GET', $url, ['timeout' => 10]);
            $statusCode = $response->getStatusCode();
            $headers = $response->getHeaders(false);
            $content = $response->getContent(false);
        } catch (TransportExceptionInterface $exception) {
            throw new ImageDownloadException(sprintf('Image download failed for "%s".', $url), previous: $exception);
        }

        if ($statusCode >= 400) {
            throw new ImageDownloadException(sprintf('Image download returned HTTP %d for "%s".', $statusCode, $url));
        }

        if ($content === '') {
            throw new ImageDownloadException(sprintf('Image download returned empty content for "%s".', $url));
        }

        $extension = $this->resolveExtension($url, $headers['content-type'][0] ?? null);
        $relativePath = sprintf('/uploads/products/%s/%s.%s', substr($urlHash, 0, 2), $urlHash, $extension);
        $absolutePath = $this->projectDir.'/public'.$relativePath;

        $directory = dirname($absolutePath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new ImageDownloadException(sprintf('Could not create image storage directory "%s".', $directory));
        }

        if (file_put_contents($absolutePath, $content) === false) {
            throw new ImageDownloadException(sprintf('Could not write image file "%s".', $absolutePath));
        }

        return $relativePath;
    }

    private function resolveExtension(string $url, ?string $contentType): string
    {
        $contentType = $contentType === null ? '' : strtolower(strtok($contentType, ';') ?: '');

        return match ($contentType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => $this->extensionFromUrl($url),
        };
    }

    private function extensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = $path === null ? '' : strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $extension : 'jpg';
    }
}
