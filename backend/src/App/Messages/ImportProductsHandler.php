<?php

declare(strict_types=1);

namespace App\Messages;

use App\Services\ImportService;
use App\Entities\ImportTask;
use App\Enums\ImportStatus;
use App\Repositories\ImportTaskRepository;
use Doctrine\ORM\EntityManager;

class ImportProductsHandler
{
    public function __construct(
        private ImportService $importService,
        private ImportTaskRepository $taskRepo,
        private EntityManager $em,
    ) {
    }

    public function __invoke(ImportProductsMessage $message): void
    {
        $task = $this->taskRepo->find($message->getTaskId());

        if ($task === null) {
            return;
        }

        $task->setStatus(ImportStatus::Processing);
        $this->em->flush();

        try {
            $result = $this->importService->processFile($message->getFilePath());

            if ($result->hasErrors() && $result->getImported() + $result->getUpdated() > 0) {
                $task->setStatus(ImportStatus::CompletedWithErrors);
            } elseif ($result->hasErrors()) {
                $task->setStatus(ImportStatus::Failed);
            } else {
                $task->setStatus(ImportStatus::Completed);
            }
            $task->setResult(json_encode($result->toArray()));
        } catch (\Exception $e) {
            $task->setStatus(ImportStatus::Failed);
            $task->setResult(json_encode([
                'total_rows' => 0,
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [['row' => 0, 'error' => $e->getMessage()]],
                'errors_count' => 1,
            ]));
        } finally {
            @unlink($message->getFilePath());
        }

        $this->em->flush();
    }
}
