<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entities\Product;
use App\Repositories\ProductAttributeRepository;
use App\Repositories\ProductImageRepository;
use App\Repositories\ProductRepository;
use App\Services\ImageDownloadService;
use App\Services\ImportService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ImportServiceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/import_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        if ($files) {
            array_map('unlink', $files);
        }
        rmdir($this->tempDir);
    }

    public function testProcessFileReturnsErrorOnNonexistentFile(): void
    {
        $service = $this->createService();
        $result = $service->processFile('/nonexistent/file.xlsx');

        $this->assertTrue($result->hasErrors());
        $this->assertStringContainsString('File processing failed', $result->getErrors()[0]['error']);
    }

    public function testProcessFileReturnsErrorOnEmptyFile(): void
    {
        // Create an xlsx with only headers, no data
        $filePath = $this->createSpreadsheet([], ['ID', 'Name', 'Code', 'Price']);

        $service = $this->createService();
        $result = $service->processFile($filePath);

        $this->assertTrue($result->hasErrors());
        $this->assertEquals(0, $result->getErrors()[0]['row']);
        $this->assertStringContainsString('empty', $result->getErrors()[0]['error']);
    }

    public function testProcessRowCreatesNewProduct(): void
    {
        $em = $this->createEntityManagerMock();
        $productRepo = $this->createMock(ProductRepository::class);
        $attributeRepo = $this->createMock(ProductAttributeRepository::class);
        $imageRepo = $this->createMock(ProductImageRepository::class);
        $imageService = $this->createMock(ImageDownloadService::class);

        $productRepo->method('findByExternalCode')->willReturn(null);
        $productRepo->expects($this->once())->method('save')->with($this->isInstanceOf(Product::class));

        $service = new ImportService($em, $productRepo, $attributeRepo, $imageRepo, $imageService);

        $filePath = $this->createSpreadsheet(
            [
                [null, null, null, null, 'Test Product', 'EXT-001', null, null, 99.99, null, 'Description', 50.0],
            ],
            [null, null, null, null, 'Name', 'ExternalCode', null, null, 'Price', null, 'Description', 'PurchasePrice']
        );

        $result = $service->processFile($filePath);

        $this->assertFalse($result->hasErrors());
        $this->assertEquals(1, $result->getImported());
        $this->assertEquals(0, $result->getUpdated());
    }

    public function testProcessRowSkipsRowWithMissingExternalCode(): void
    {
        $em = $this->createEntityManagerMock();
        $productRepo = $this->createMock(ProductRepository::class);
        $attributeRepo = $this->createMock(ProductAttributeRepository::class);
        $imageRepo = $this->createMock(ProductImageRepository::class);
        $imageService = $this->createMock(ImageDownloadService::class);

        $service = new ImportService($em, $productRepo, $attributeRepo, $imageRepo, $imageService);

        // Row with empty external code (column index 5)
        $filePath = $this->createSpreadsheet(
            [
                [null, null, null, null, 'Test Product', null, null, null, 99.99, null, 'Desc', 50.0],
            ],
            [null, null, null, null, 'Name', 'ExternalCode', null, null, 'Price', null, 'Description', 'PurchasePrice']
        );

        $result = $service->processFile($filePath);

        $this->assertTrue($result->hasErrors());
        $this->assertEquals(1, $result->getSkipped());
        $this->assertStringContainsString('Missing external code', $result->getErrors()[0]['error']);
    }

    public function testProcessRowSkipsRowWithMissingName(): void
    {
        $em = $this->createEntityManagerMock();
        $productRepo = $this->createMock(ProductRepository::class);
        $attributeRepo = $this->createMock(ProductAttributeRepository::class);
        $imageRepo = $this->createMock(ProductImageRepository::class);
        $imageService = $this->createMock(ImageDownloadService::class);

        $service = new ImportService($em, $productRepo, $attributeRepo, $imageRepo, $imageService);

        // Row with external code but empty name (column index 4)
        $filePath = $this->createSpreadsheet(
            [
                [null, null, null, null, null, 'EXT-001', null, null, 99.99, null, 'Desc', 50.0],
            ],
            [null, null, null, null, 'Name', 'ExternalCode', null, null, 'Price', null, 'Description', 'PurchasePrice']
        );

        $result = $service->processFile($filePath);

        $this->assertTrue($result->hasErrors());
        $this->assertEquals(1, $result->getSkipped());
        $this->assertStringContainsString('Missing product name', $result->getErrors()[0]['error']);
    }

    public function testParsePriceHandlesCommaDecimal(): void
    {
        $service = $this->createService();

        // Use reflection to test private method
        $method = new \ReflectionMethod($service, 'parsePrice');

        $this->assertEquals(99.99, $method->invoke($service, '99,99'));
        $this->assertEquals(1500.50, $method->invoke($service, '1 500,50'));
        $this->assertEquals(0.0, $method->invoke($service, null));
        $this->assertEquals(0.0, $method->invoke($service, ''));
        $this->assertEquals(42.0, $method->invoke($service, 42));
    }

    public function testParsePriceStripsNonNumericChars(): void
    {
        $service = $this->createService();
        $method = new \ReflectionMethod($service, 'parsePrice');

        $this->assertEquals(99.99, $method->invoke($service, '$99.99'));
        $this->assertEquals(100.0, $method->invoke($service, '€100'));
        $this->assertEquals(50.0, $method->invoke($service, '50 руб'));
    }

    public function testTransactionRollbackOnFlushFailure(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction')->willReturn(true);
        $connection->method('rollBack')->willReturn(true);
        $connection->method('executeStatement')->willReturn(0);

        $em = $this->createMock(EntityManager::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getRepository')->willReturn($this->createMock(\Doctrine\ORM\EntityRepository::class));
        $em->expects($this->atLeastOnce())->method('flush')->willThrowException(new \RuntimeException('DB error'));

        $productRepo = $this->createMock(ProductRepository::class);
        $productRepo->method('findByExternalCode')->willReturn(null);

        $attributeRepo = $this->createMock(ProductAttributeRepository::class);
        $imageRepo = $this->createMock(ProductImageRepository::class);
        $imageService = $this->createMock(ImageDownloadService::class);

        $service = new ImportService($em, $productRepo, $attributeRepo, $imageRepo, $imageService);

        $filePath = $this->createSpreadsheet(
            [
                [null, null, null, null, 'Test', 'EXT-001', null, null, 100, null, 'Desc', 50],
            ],
            [null, null, null, null, 'Name', 'ExternalCode', null, null, 'Price', null, 'Description', 'PurchasePrice']
        );

        $result = $service->processFile($filePath);

        $this->assertTrue($result->hasErrors());
        $this->assertCount(1, $result->getErrors());
    }

    private function createService(): ImportService
    {
        $em = $this->createEntityManagerMock();

        return new ImportService(
            $em,
            $this->createMock(ProductRepository::class),
            $this->createMock(ProductAttributeRepository::class),
            $this->createMock(ProductImageRepository::class),
            $this->createMock(ImageDownloadService::class),
        );
    }

    private function createEntityManagerMock(): EntityManager
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction')->willReturn(true);
        $connection->method('commit')->willReturn(true);
        $connection->method('rollBack')->willReturn(true);

        $em = $this->createMock(EntityManager::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getRepository')->willReturn($this->createMock(\Doctrine\ORM\EntityRepository::class));

        return $em;
    }

    private function createSpreadsheet(array $dataRows, array $headers): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write headers
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $header);
        }

        // Write data rows
        foreach ($dataRows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 2, $value);
            }
        }

        $filePath = $this->tempDir . '/test_' . uniqid() . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($filePath);

        return $filePath;
    }
}
