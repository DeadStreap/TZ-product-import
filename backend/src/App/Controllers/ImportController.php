<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ImportService;
use App\Messages\ImportProductsMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class ImportController
{
    public function __construct(
        private ImportService $importService,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function import(Request $request, Response $response): Response
    {
        $uploadedFiles = $request->getUploadedFiles();

        if (empty($uploadedFiles['file'])) {
            return $this->jsonError($response, 'No file uploaded', 400);
        }

        $file = $uploadedFiles['file'];

        $allowedTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
        ];

        $mimeType = $file->getClientMediaType();
        if (!in_array($mimeType, $allowedTypes, true)) {
            return $this->jsonError($response, 'Invalid file type. Only XLSX allowed.', 400);
        }

        if ($file->getSize() > 50 * 1024 * 1024) {
            return $this->jsonError($response, 'File too large. Max 50MB.', 400);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'import_');
        $file->moveTo($tempFile);

        $taskId = rand(1, 999999);
        $taskDir = sys_get_temp_dir() . '/import_tasks';

        if (!is_dir($taskDir)) {
            mkdir($taskDir, 0755, true);
        }

        $taskFile = $taskDir . '/task_' . $taskId . '.json';
        file_put_contents($taskFile, json_encode([
            'status' => 'pending',
            'file' => $tempFile,
        ]));

        $this->messageBus->dispatch(new ImportProductsMessage($taskId, $tempFile));

        $response->getBody()->write(json_encode([
            'task_id' => $taskId,
            'status' => 'pending',
            'message' => 'Import started. Check status at /api/import/' . $taskId . '/status',
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(202);
    }

    public function status(Request $request, Response $response, array $args): Response
    {
        $taskId = (int) $args['id'];
        $taskFile = sys_get_temp_dir() . '/import_tasks/task_' . $taskId . '.json';

        if (!file_exists($taskFile)) {
            return $this->jsonError($response, 'Task not found', 404);
        }

        $data = json_decode(file_get_contents($taskFile), true);

        $response->getBody()->write(json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    private function jsonError(Response $response, string $message, int $status): Response
    {
        $response->getBody()->write(json_encode(['error' => $message]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
