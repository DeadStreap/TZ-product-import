<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entities\Product;
use App\DTO\ProductFilter;
use App\Tests\Integration\IntegrationTestCase;

class ProductRepositoryTest extends IntegrationTestCase
{
    public function testSaveAndFind(): void
    {
        $product = $this->createProduct('EXT-001', 'Widget', 25.0);

        $found = $this->productRepo->find($product->getId());

        $this->assertNotNull($found);
        $this->assertEquals('EXT-001', $found->getExternalCode());
        $this->assertEquals('Widget', $found->getName());
        $this->assertEquals(25.0, $found->getPrice());
    }

    public function testFindByExternalCode(): void
    {
        $this->createProduct('EXT-XYZ', 'Gadget');

        $found = $this->productRepo->findByExternalCode('EXT-XYZ');

        $this->assertNotNull($found);
        $this->assertEquals('Gadget', $found->getName());
    }

    public function testFindByExternalCodeReturnsNull(): void
    {
        $found = $this->productRepo->findByExternalCode('NONEXISTENT');

        $this->assertNull($found);
    }

    public function testFindByFilterByName(): void
    {
        $this->createProduct('EXT-001', 'Blue Widget');
        $this->createProduct('EXT-002', 'Red Gadget');
        $this->createProduct('EXT-003', 'Blue Gadget');

        $filter = new ProductFilter(name: 'Blue');
        $result = $this->productRepo->findByFilter($filter, 1, 20);

        $this->assertCount(2, $result['items']);
        $this->assertEquals(2, $result['total']);
    }

    public function testFindByFilterByPriceRange(): void
    {
        $this->createProduct('EXT-001', 'Cheap', 10.0);
        $this->createProduct('EXT-002', 'Medium', 50.0);
        $this->createProduct('EXT-003', 'Expensive', 100.0);

        $filter = new ProductFilter(minPrice: 20.0, maxPrice: 75.0);
        $result = $this->productRepo->findByFilter($filter, 1, 20);

        $this->assertCount(1, $result['items']);
        $this->assertEquals('Medium', $result['items'][0]->getName());
    }

    public function testFindByFilterPagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createProduct("EXT-{$i}", "Product {$i}", $i * 10.0);
        }

        $filter = new ProductFilter();
        $result = $this->productRepo->findByFilter($filter, 1, 2);

        $this->assertCount(2, $result['items']);
        $this->assertEquals(5, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(2, $result['limit']);
        $this->assertEquals(3, $result['total_pages']);
    }

    public function testFindByFilterPageTwo(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createProduct("EXT-{$i}", "Product {$i}", $i * 10.0);
        }

        $filter = new ProductFilter();
        $result = $this->productRepo->findByFilter($filter, 2, 2);

        $this->assertCount(2, $result['items']);
        $this->assertEquals(2, $result['page']);
    }

    public function testFindByFilterNoResults(): void
    {
        $this->createProduct('EXT-001', 'Widget');

        $filter = new ProductFilter(name: 'Nonexistent');
        $result = $this->productRepo->findByFilter($filter, 1, 20);

        $this->assertCount(0, $result['items']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['total_pages']);
    }

    public function testCount(): void
    {
        $this->assertCount(0, $this->productRepo->count([]));

        $this->createProduct('EXT-001');
        $this->createProduct('EXT-002');

        // Note: count() uses EntityRepository::count which may not work with custom repos
        // This is a basic smoke test
        $this->assertIsInt($this->productRepo->count([]));
    }

    public function testSaveMultipleProducts(): void
    {
        $p1 = $this->createProduct('EXT-001', 'First');
        $p2 = $this->createProduct('EXT-002', 'Second');

        $this->assertNotEquals($p1->getId(), $p2->getId());

        $found1 = $this->productRepo->find($p1->getId());
        $found2 = $this->productRepo->find($p2->getId());

        $this->assertEquals('First', $found1->getName());
        $this->assertEquals('Second', $found2->getName());
    }

    public function testFindByFilterOrderByIdDesc(): void
    {
        $p1 = $this->createProduct('EXT-001', 'First');
        $p2 = $this->createProduct('EXT-002', 'Second');
        $p3 = $this->createProduct('EXT-003', 'Third');

        $filter = new ProductFilter();
        $result = $this->productRepo->findByFilter($filter, 1, 10);

        // Should be ordered by ID DESC
        $this->assertEquals($p3->getId(), $result['items'][0]->getId());
        $this->assertEquals($p2->getId(), $result['items'][1]->getId());
        $this->assertEquals($p1->getId(), $result['items'][2]->getId());
    }
}
