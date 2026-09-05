<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entities\ImportTask;
use App\Enums\ImportStatus;
use PHPUnit\Framework\TestCase;

class ImportTaskTest extends TestCase
{
    public function testDefaultStatusIsPending(): void
    {
        $task = new ImportTask();

        $this->assertEquals(ImportStatus::Pending, $task->getStatus());
    }

    public function testSetStatus(): void
    {
        $task = new ImportTask();
        $task->setStatus(ImportStatus::Processing);

        $this->assertEquals(ImportStatus::Processing, $task->getStatus());
    }

    public function testSetResult(): void
    {
        $task = new ImportTask();
        $result = json_encode(['imported' => 10, 'errors' => []]);
        $task->setResult($result);

        $this->assertEquals($result, $task->getResult());
    }

    public function testToArrayWithNullResult(): void
    {
        $task = new ImportTask();

        $array = $task->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertEquals('pending', $array['status']);
        $this->assertNull($array['result']);
        $this->assertArrayHasKey('created_at', $array);
    }

    public function testToArrayWithJsonResult(): void
    {
        $task = new ImportTask();
        $task->setStatus(ImportStatus::Completed);
        $result = json_encode(['imported' => 5, 'updated' => 2, 'errors' => []]);
        $task->setResult($result);

        $array = $task->toArray();

        $this->assertEquals('completed', $array['status']);
        $this->assertIsArray($array['result']);
        $this->assertEquals(5, $array['result']['imported']);
        $this->assertEquals(2, $array['result']['updated']);
    }

    public function testCreatedAtIsSetOnConstruct(): void
    {
        $task = new ImportTask();

        $this->assertInstanceOf(\DateTimeImmutable::class, $task->getCreatedAt());
    }

    public function testAllStatuses(): void
    {
        $task = new ImportTask();

        foreach (ImportStatus::cases() as $status) {
            $task->setStatus($status);
            $this->assertEquals($status, $task->getStatus());
        }
    }
}
