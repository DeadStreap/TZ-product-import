<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;

class ImageDownloadService
{
    private Client $client;
    private string $uploadDir;
    private int $maxFileSize;

    public function __construct(string $uploadDir, int $maxFileSize = 10 * 1024 * 1024)
    {
        $this->uploadDir = $uploadDir;
        $this->maxFileSize = $maxFileSize;
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    public function download(string $url): ?string
    {
        try {
            $response = $this->client->get($url);
            $contentType = $response->getHeaderLine('Content-Type');

            if (!str_starts_with($contentType, 'image/')) {
                return null;
            }

            $contentLength = $response->getHeaderLine('Content-Length');
            if ($contentLength !== '' && (int) $contentLength > $this->maxFileSize) {
                error_log('Image too large (' . $contentLength . ' bytes): ' . $url);

                return null;
            }

            $extension = $this->getExtensionFromMime($contentType);
            $filename = md5($url) . '.' . $extension;
            $subDir = date('Y/m');
            $fullDir = $this->uploadDir . '/' . $subDir;

            if (!is_dir($fullDir)) {
                mkdir($fullDir, 0755, true);
            }

            $filepath = $subDir . '/' . $filename;
            file_put_contents($this->uploadDir . '/' . $filepath, $response->getBody());

            return $filepath;
        } catch (\Exception $e) {
            error_log('Failed to download image ' . $url . ': ' . $e->getMessage());

            return null;
        }
    }

    private function getExtensionFromMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => 'jpg',
        };
    }
}
