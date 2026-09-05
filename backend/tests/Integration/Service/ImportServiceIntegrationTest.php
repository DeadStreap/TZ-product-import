<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Services\ImageDownloadService;
use App\Services\ImportService;
use App\Tests\Integration\IntegrationTestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ImportServiceIntegrationTest extends IntegrationTestCase
{
    private string $tempDir;
    private ImageDownloadService $imageServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/import_integration_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        if ($files) {
            array_map('unlink', $files);
        }
        rmdir($this->tempDir);

        parent::tearDown();
    }

    public function testProcessFileWithValidSpreadsheet(): void
    {
        $service = $this->createImportService();

        $filePath = $this->createSpreadsheet(
            [
                [null, null, null, null, 'Widget', 'EXT-001', null, null, 99.99, null, 'A widget', 50.0],
            ],
            [null, null, null, null, 'Name', 'ExternalCode', null, null, 'Price', null, 'Description', 'PurchasePrice']
        );

        $result = $service->processFile($filePath);

        $this->assertFalse($result->hasErrors());
        $this->assertEquals(1, $result->getImported());
        $this->assertEquals(0, $result->getUpdated());

        // Verify product was created in DB
        $product = $this->productRepo->findByExternalCode('EXT-001');
        $this->assertNotNull($product);
        $this->assertEquals('Widget', $product->getName());
        $this->assertEquals(99.99, $product->getPrice());
        $this->assertEquals(50.0, $product->getPurchasePrice());
        $this->assertEqualsWithDelta(50.0, $product->getDiscount(), 0.1);
    }

    public function testProcessFileUpdatesExistingProduct(): void
    {
        // Create existing product
        $existing = $this->createProduct('EXT-001', 'Old Widget', 50.0, 25.0);

        $service = $this->createImportService();

        $filePath = $this->createSpreadsheet(
            [
                [null, null, null, null, 'New Widget', 'EXT-001', null, null, 199.99, null, 'Updated', 100.0],
            ],
            [null, null, null, null, 'Name', 'ExternalCode', null, null, 'Price', null, 'Description', 'PurchasePrice']
        );

        $result = $service->processFile($filePath);

        $this->assertFalse($result->hasErrors());
        $this->assertEquals(0, $result->getImported());
        $this->assertEquals(1, $result->getUpdated());

        // Verify product was updated
        $product = $this->productRepo->findByExternalCode('EXT-001');
        $this->assertEquals('New Widget', $product->getName());
        $this->assertEquals(199.99, $product->getPrice());
    }

    public function testProcessFileHandlesAttributes(): void
    {
        $service = $this->createImportService();

        $filePath = $this->createSpreadsheet(
            [
                [null, null, null, null, 'Widget', 'EXT-001', null, null, 99.99, null, 'Desc', 50.0, 'Red', 'Large'],
            ],
            [null, null, null, null, 'Name', 'ExternalCode', null, null, 'Price', null, 'Description', 'PurchasePrice', 'Доп. поле: Color', 'Доп. поле: Size']
        );

        $result = $service->processFile($filePath);

        $this->assertFalse($result->hasErrors());

        $product = $this->productRepo->findByExternalCode('EXT-001');
        $attributes = $this->attributeRepo->findByProduct($product->getId());

        $this->assertCount(2, $attributes);

        $keys = array_map(fn($a) => $a->getKey(), $attributes);
        $this->assertContains('Color', $keys);
        $this->assertContains('Size', $keys);
    }

    public function testProcessFileSkipsEmptyAttributes(): void
    {
        $service = $this->createImportService();

        $filePath = $this->createSpreadsheet(
            [
                [null, null, null, null, 'Widget', 'EXT-001', null, null, 99.99, null, 'Desc', 50.0, 'Red', ''],
            ],
            [null, null, null, null, 'Name', 'ExternalCode', null, null, 'Price', null, 'Description', 'PurchasePrice', 'Доп. поле: Color', 'Доп. поле: Size']
        );

        $result = $service->processFile($filePath);

        $product = $this->productRepo->findByExternalCode('EXT-001');
        $attributes = $this->attributeRepo->findByProduct($product->getId());

        // Only Color should be created, Size is empty
        $this->assertCount(1, $attributes);
        $this->assertEquals('Color', $attributes[0]->getKey());
    }

    public function testProcessFileHandlesMultipleProducts(): void
    {
        $service = $this->createImportService();

        $filePath = $this->createSpreadsheet(
            [
                [null, null, null, null, 'Widget A', 'EXT-001', null, null, 10.0, null, 'Desc A', 5.0],
                [null, null, null, null, 'Widget B', 'EXT-002', null, null, 20.0, null, 'Desc B', 10.0],
                [null, null, null, null, 'Widget C', 'EXT-003', null, null, 30.0, null, 'Desc C', 15.0],
            ],
            [null, null, null, null, 'Name', 'ExternalCode', null, null, 'Price', null, 'Description', 'PurchasePrice']
        );

        $result = $service->processFile($filePath);

        $this->assertFalse($result->hasErrors());
        $this->assertEquals(3, $result->getImported());

        $this->assertNotNull($this->productRepo->findByExternalCode('EXT-001'));
        $this->assertNotNull($this->productRepo->findByExternalCode('EXT-002'));
        $this->assertNotNull($this->productRepo->findByExternalCode('EXT-003'));
    }

    public function testProcessFileSkipsInvalidRowsAndContinues(): void
    {
        $service = $this->createImportService();

        $filePath = $this->createSpreadsheet(
            [
                [null, null, null, null, 'Good Widget', 'EXT-001', null, null, 99.99, null, 'Desc', 50.0],
                [null, null, null, null, '', 'EXT-002', null, null, 0, null, null, null], // Missing name
                [null, null, null, null, 'Another Widget', 'EXT-003', null, null, 49.99, null, 'Desc2', 25.0],
            ],
            [null, null, null, null, 'Name', 'ExternalCode', null, null, 'Price', null, 'Description', 'PurchasePrice']
        );

        $result = $service->processFile($filePath);

        // First and third should be imported, second skipped
        $this->assertEquals(2, $result->getImported());
        $this->assertEquals(1, $result->getSkipped());
        $this->assertTrue($result->hasErrors());

        $this->assertNotNull($this->productRepo->findByExternalCode('EXT-001'));
        $this->assertNull($this->productRepo->findByExternalCode('EXT-002'));
        $this->assertNotNull($this->productRepo->findByExternalCode('EXT-003'));
    }

    public function testProcessFileClearsOldAttributesOnUpdate(): void
    {
        // Create product with existing attributes
        $product = $this->createProduct('EXT-001', 'Widget', 99.99, 50.0);

        $attr = new \App\Entities\ProductAttribute();
        $attr->setProduct($product);
        $attr->setKey('OldAttr');
        $attr->setValue('old');
        $this->em->persist($attr);
        $this->em->flush();

        $service = $this->createImportService();

        // Import with new attributes
        $filePath = $this->createSpreadsheet(
            [
                [null, null, null, null, 'Widget Updated', 'EXT-001', null, null, 149.99, null, 'Desc', 75.0, 'NewVal'],
            ],
            [null, null, null, null, 'Name', 'ExternalCode', null, null, 'Price', null, 'Description', 'PurchasePrice', 'Доп. поле: NewAttr']
        );

        $result = $service->processFile($filePath);

        $this->assertEquals(1, $result->getUpdated());

        // Old attributes should be gone
        $attributes = $this->attributeRepo->findByProduct($product->getId());
        $keys = array_map(fn($a) => $a->getKey(), $attributes);

        $this->assertNotContains('OldAttr', $keys);
        $this->assertContains('NewAttr', $keys);
    }

    private function createImportService(): ImportService
    {
        $imageService = $this->createMock(ImageDownloadService::class);
        $imageService->method('download')->willReturn('2026/09/mock.jpg');

        return new ImportService(
            $this->em,
            $this->productRepo,
            $this->attributeRepo,
            $this->imageRepo,
            $imageService,
        );
    }

    private function createSpreadsheet(array $dataRows, array $headers): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $colIndex => $header) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($col . '1', $header);
        }

        foreach ($dataRows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue($col . ($rowIndex + 2), $value);
            }
        }

        $filePath = $this->tempDir . '/test_' . uniqid() . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($filePath);

        return $filePath;
    }
}
