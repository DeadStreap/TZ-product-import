<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use App\Repositories\ProductRepository;
use App\Repositories\ProductAttributeRepository;
use App\Repositories\ProductImageRepository;

abstract class IntegrationTestCase extends TestCase
{
    protected EntityManager $em;
    protected ProductRepository $productRepo;
    protected ProductAttributeRepository $attributeRepo;
    protected ProductImageRepository $imageRepo;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [dirname(__DIR__, 2) . '/src/App/Entities'],
            isDevMode: true,
        );

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => ':memory:',
        ]);

        $this->em = new EntityManager($connection, $config);

        // Create schema
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // Initialize repositories
        $this->productRepo = new ProductRepository($this->em);
        $this->attributeRepo = new ProductAttributeRepository($this->em);
        $this->imageRepo = new ProductImageRepository($this->em);
    }

    protected function tearDown(): void
    {
        $this->em->close();
    }

    protected function createProduct(
        string $externalCode = 'EXT-001',
        string $name = 'Test Product',
        float $price = 99.99,
        ?float $purchasePrice = 50.0,
    ): \App\Entities\Product {
        $product = new \App\Entities\Product();
        $product->setExternalCode($externalCode);
        $product->setName($name);
        $product->setPrice($price);
        $product->setPurchasePrice($purchasePrice);
        $product->calculateDiscount();

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }
}
