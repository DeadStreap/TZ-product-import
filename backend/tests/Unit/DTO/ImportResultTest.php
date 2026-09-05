<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ImportResult;
use PHPUnit\Framework\TestCase;

class ImportResultTest extends TestCase
{
    public function testInitialState(): void
    {
        $result = new ImportResult();

        $this->assertEquals(0, $result->getTotalRows());
        $this->assertEquals(0, $result->getImported());
        $this->assertEquals(0, $result->getUpdated());
        $this->assertEquals(0, $result->getSkipped());
        $this->assertEmpty($result->getErrors());
        $this->assertFalse($result->hasErrors());
    }

    public function testAddImportedIncrementsCounter(): void
    {
        $result = new ImportResult();
        $result->addImported();
        $result->addImported();

        $this->assertEquals(2, $result->getImported());
    }

    public function testAddUpdatedIncrementsCounter(): void
    {
        $result = new ImportResult();
        $result->addUpdated();
        $result->addUpdated();
        $result->addUpdated();

        $this->assertEquals(3, $result->getUpdated());
    }

    public function testAddSkippedIncrementsCounter(): void
    {
        $result = new ImportResult();
        $result->addSkipped();

        $this->assertEquals(1, $result->getSkipped());
    }

    public function testAddErrorAppendsToErrorsArray(): void
    {
        $result = new ImportResult();
        $result->addError(2, 'Missing name');
        $result->addError(5, 'Invalid price');

        $errors = $result->getErrors();
        $this->assertCount(2, $errors);
        $this->assertEquals(2, $errors[0]['row']);
        $this->assertEquals('Missing name', $errors[0]['error']);
        $this->assertEquals(5, $errors[1]['row']);
        $this->assertEquals('Invalid price', $errors[1]['error']);
    }

    public function testHasErrorsReturnsTrueWhenErrorsExist(): void
    {
        $result = new ImportResult();
        $this->assertFalse($result->hasErrors());

        $result->addError(1, 'Error');
        $this->assertTrue($result->hasErrors());
    }

    public function testSetTotalRows(): void
    {
        $result = new ImportResult();
        $result->setTotalRows(100);

        $this->assertEquals(100, $result->getTotalRows());
    }

    public function testToArrayReturnsCorrectShape(): void
    {
        $result = new ImportResult();
        $result->setTotalRows(10);
        $result->addImported();
        $result->addImported();
        $result->addUpdated();
        $result->addError(3, 'Bad row');

        $array = $result->toArray();

        $this->assertEquals(10, $array['total_rows']);
        $this->assertEquals(2, $array['imported']);
        $this->assertEquals(1, $array['updated']);
        $this->assertEquals(0, $array['skipped']);
        $this->assertCount(1, $array['errors']);
        $this->assertEquals(1, $array['errors_count']);
    }

    public function testToArrayWithNoErrors(): void
    {
        $result = new ImportResult();
        $array = $result->toArray();

        $this->assertEquals(0, $array['errors_count']);
        $this->assertEmpty($array['errors']);
    }
}
