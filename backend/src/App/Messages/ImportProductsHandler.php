<?php

declare(strict_types=1);

namespace App\Messages;

use App\Services\ImportService;

class ImportProductsHandler
{
    public function __construct(
        private ImportService $importService,
    ) {
    }

    public function __invoke(ImportProductsMessage $message): void
    {
        $result = $this->importService->processFile($message->getFilePath());

        $taskFile = $this->getTaskFilePath($message->getTaskId());
        file_put_contents($taskFile, json_encode([
            'status' => $result->hasErrors() ? 'completed_with_errors' : 'completed',
            'result' => $result->toArray(),
        ]));
    }

    private function getTaskFilePath(int $taskId): string
    {
        $dir = sys_get_temp_dir() . '/import_tasks';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir . '/task_' . $taskId . '.json';
    }
}
