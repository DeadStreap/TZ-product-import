<?php

declare(strict_types=1);

namespace App\Services;

use Doctrine\ORM\EntityManager;
use App\Entities\Product;
use App\Entities\ProductAttribute;
use App\Entities\ProductImage;
use App\DTO\ImportResult;
use App\Repositories\ProductRepository;
use App\Repositories\ProductAttributeRepository;
use App\Repositories\ProductImageRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService
{
    private const ATTR_PREFIX = 'Доп. поле: ';

    private const IMAGE_FIELDS = [
        'Доп. поле: Ссылка на упаковку',
        'Доп. поле: Ссылки на фото',
    ];

    private const COLUMN_MAP = [
        'Код' => 'external_code',
        'Артикул' => 'article',
        'Внешний код' => 'external_code',
        'ExternalCode' => 'external_code',
        'Наименование' => 'name',
        'Название' => 'name',
        'Name' => 'name',
        'Описание' => 'description',
        'Description' => 'description',
        'Цена: Цена продажи' => 'price',
        'Цена продажи' => 'price',
        'Цена' => 'price',
        'Price' => 'price',
        'Закупочная цена' => 'purchase_price',
        'PurchasePrice' => 'purchase_price',
    ];

    private array $columnIndex = [];

    public function __construct(
        private EntityManager $em,
        private ProductRepository $productRepo,
        private ProductAttributeRepository $attributeRepo,
        private ProductImageRepository $imageRepo,
        private ImageDownloadService $imageDownloadService,
    ) {
    }

    public function processFile(string $filePath): ImportResult
    {
        $result = new ImportResult();

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (count($rows) < 2) {
                $result->addError(0, 'File is empty or has no data rows');

                return $result;
            }

            $headers = $rows[0];
            $dataRows = array_slice($rows, 1);
            $result->setTotalRows(count($dataRows));

            $this->columnIndex = $this->buildColumnIndex($headers);

            $this->em->getConnection()->beginTransaction();

            try {
                foreach ($dataRows as $index => $row) {
                    $rowNumber = $index + 2;

                    try {
                        $this->processRow($row, $headers, $result, $rowNumber);
                    } catch (\Exception $e) {
                        $result->addError($rowNumber, $e->getMessage());
                        $result->addSkipped();
                    }
                }

                $this->em->flush();
                $this->em->getConnection()->commit();
            } catch (\Exception $e) {
                $this->em->getConnection()->rollBack();
                $result->addError(0, 'Transaction failed: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            $result->addError(0, 'File processing failed: ' . $e->getMessage());
        }

        return $result;
    }

    private function buildColumnIndex(array $headers): array
    {
        $index = [];

        foreach ($headers as $colIndex => $header) {
            if (!is_string($header)) {
                continue;
            }

            $header = trim($header);

            if (isset(self::COLUMN_MAP[$header])) {
                $index[self::COLUMN_MAP[$header]] = $colIndex;
            }
        }

        return $index;
    }

    private function getCellValue(array $row, string $field): mixed
    {
        $colIndex = $this->columnIndex[$field] ?? null;

        return $colIndex !== null ? ($row[$colIndex] ?? null) : null;
    }

    private function processRow(array $row, array $headers, ImportResult $result, int $rowNumber): void
    {
        $externalCode = $this->getCellValue($row, 'external_code');
        if (empty($externalCode)) {
            $externalCode = $this->getCellValue($row, 'article');
        }

        if (empty($externalCode)) {
            throw new \RuntimeException('Missing external code');
        }

        $name = $this->getCellValue($row, 'name');
        if (empty($name)) {
            throw new \RuntimeException('Missing product name');
        }

        $price = $this->parsePrice($this->getCellValue($row, 'price'));
        $purchasePrice = $this->parsePrice($this->getCellValue($row, 'purchase_price'));

        $product = $this->productRepo->findByExternalCode((string) $externalCode);
        $isNew = $product === null;

        if ($isNew) {
            $product = new Product();
            $product->setExternalCode((string) $externalCode);
        }

        $product->setName((string) $name);
        $product->setDescription($this->getCellValue($row, 'description'));
        $product->setPrice($price);
        $product->setPurchasePrice($purchasePrice);
        $product->calculateDiscount();

        $this->productRepo->save($product);

        if (!$isNew) {
            $this->attributeRepo->deleteByProduct($product->getId());
            $this->imageRepo->deleteByProduct($product->getId());
        }

        foreach ($headers as $colIndex => $header) {
            if (is_string($header) && str_starts_with($header, self::ATTR_PREFIX)) {
                $attrName = substr($header, strlen(self::ATTR_PREFIX));
                $attrValue = $row[$colIndex] ?? null;

                if ($attrValue !== null && $attrValue !== '') {
                    $attribute = new ProductAttribute();
                    $attribute->setProduct($product);
                    $attribute->setKey($attrName);
                    $attribute->setValue((string) $attrValue);
                    $this->attributeRepo->save($attribute);
                }
            }
        }

        $this->processImages($row, $headers, $product);

        if ($isNew) {
            $result->addImported();
        } else {
            $result->addUpdated();
        }
    }

    private function processImages(array $row, array $headers, Product $product): void
    {
        foreach (self::IMAGE_FIELDS as $field) {
            $colIndex = array_search($field, $headers);
            if ($colIndex === false) {
                continue;
            }

            $value = $row[$colIndex] ?? null;
            if (empty($value)) {
                continue;
            }

            $urls = array_map('trim', explode(',', (string) $value));

            foreach ($urls as $url) {
                if ($url === '') {
                    continue;
                }

                $localPath = $this->imageDownloadService->download($url);

                if ($localPath === null) {
                    continue;
                }

                $image = new ProductImage();
                $image->setProduct($product);
                $image->setUrl($url);
                $image->setPath($localPath);
                $this->imageRepo->save($image);
            }
        }
    }

    private function parsePrice(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $cleaned = str_replace(',', '.', (string) $value);
        $cleaned = preg_replace('/[^\d.\-]/', '', $cleaned);

        return (float) $cleaned;
    }
}
