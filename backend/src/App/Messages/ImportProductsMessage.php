<?php

declare(strict_types=1);

namespace App\Messages;

class ImportProductsMessage
{
    public function __construct(
        private int $taskId,
        private string $filePath,
    ) {}

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
