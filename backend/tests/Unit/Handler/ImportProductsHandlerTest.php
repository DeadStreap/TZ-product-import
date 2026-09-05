<?php

declare(strict_types=1);

namespace App\Tests\Unit\Handler;

use App\Messages\ImportProductsHandler;
use App\Messages\ImportProductsMessage;
use App\Services\ImportService;
use App\DTO\ImportResult;
use App\Entities\ImportTask;
use App\Enums\ImportStatus;
use App\Repositories\ImportTaskRepository;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

class ImportProductsHandlerTest extends TestCase
{
    private ImportProductsHandler $handler;
    private ImportService $importService;
    private ImportTaskRepository $taskRepo;
    private EntityManager $em;

    protected function setUp(): void
    {
        $this->importService = $this->createMock(ImportService::class);
        $this->taskRepo = $this->createMock(ImportTaskRepository::class);
        $this->em = $this->createMock(EntityManager::class);

        $this->handler = new ImportProductsHandler(
            $this->importService,
            $this->taskRepo,
            $this->em,
        );
    }

    public function testInvokeSkipsWhenTaskNotFound(): void
    {
        $this->taskRepo->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn(null);

        $this->importService->expects($this->never())->method('processFile');

        ($this->handler)(new ImportProductsMessage(42, '/tmp/test.xlsx'));
    }

    public function testInvokeSetsProcessingThenCompletedOnSuccess(): void
    {
        $task = new ImportTask();
        $this->taskRepo->method('find')->willReturn($task);

        $result = new ImportResult();
        $result->addImported();
        $result->addImported();
        $this->importService->method('processFile')->willReturn($result);

        $this->em->expects($this->exactly(2))->method('flush');

        ($this->handler)(new ImportProductsMessage(1, '/tmp/test.xlsx'));

        $this->assertSame(ImportStatus::Completed, $task->getStatus());
    }

    public function testInvokeSetsCompletedWithErrorsWhenPartialSuccess(): void
    {
        $task = new ImportTask();
        $this->taskRepo->method('find')->willReturn($task);

        $result = new ImportResult();
        $result->addImported();
        $result->addError(3, 'Missing name');
        $this->importService->method('processFile')->willReturn($result);

        ($this->handler)(new ImportProductsMessage(2, '/tmp/test.xlsx'));

        $this->assertSame(ImportStatus::CompletedWithErrors, $task->getStatus());
    }

    public function testInvokeSetsFailedWhenAllRowsError(): void
    {
        $task = new ImportTask();
        $this->taskRepo->method('find')->willReturn($task);

        $result = new ImportResult();
        $result->addError(1, 'Missing external code');
        $result->addError(2, 'Missing name');
        $this->importService->method('processFile')->willReturn($result);

        ($this->handler)(new ImportProductsMessage(3, '/tmp/test.xlsx'));

        $this->assertSame(ImportStatus::Failed, $task->getStatus());
    }

    public function testInvokeStoresResultAsJson(): void
    {
        $task = new ImportTask();
        $this->taskRepo->method('find')->willReturn($task);

        $result = new ImportResult();
        $result->setTotalRows(5);
        $result->addImported();
        $this->importService->method('processFile')->willReturn($result);

        ($this->handler)(new ImportProductsMessage(4, '/tmp/test.xlsx'));

        $decoded = json_decode($task->getResult(), true);
        $this->assertSame(5, $decoded['total_rows']);
        $this->assertSame(1, $decoded['imported']);
    }
}
