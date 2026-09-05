<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Entities\ImportTask;
use App\Enums\ImportStatus;
use App\Messages\ImportProductsMessage;
use App\Repositories\ImportTaskRepository;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Messenger\MessageBusInterface;

class ImportController
{
    public function __construct(
        private EntityManager $em,
        private ImportTaskRepository $taskRepo,
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
        if ($tempFile === false) {
            return $this->jsonError($response, 'Failed to create temp file', 500);
        }
        $file->moveTo($tempFile);

        $task = new ImportTask();
        $task->setStatus(ImportStatus::Pending);
        $this->taskRepo->save($task);
        $this->em->flush();

        $this->messageBus->dispatch(new ImportProductsMessage($task->getId(), $tempFile));

        $response->getBody()->write(json_encode([
            'task_id' => $task->getId(),
            'status' => 'pending',
            'message' => 'Import started. Check status at /api/import/' . $task->getId() . '/status',
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(202);
    }

    public function status(Request $request, Response $response, array $args): Response
    {
        $taskId = (int) $args['id'];
        $task = $this->taskRepo->find($taskId);

        if ($task === null) {
            return $this->jsonError($response, 'Task not found', 404);
        }

        $response->getBody()->write(json_encode($task->toArray()));

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
