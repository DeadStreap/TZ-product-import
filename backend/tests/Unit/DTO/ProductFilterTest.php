<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ProductFilter;
use PHPUnit\Framework\TestCase;

class ProductFilterTest extends TestCase
{
    public function testFromRequestWithAllParams(): void
    {
        $filter = ProductFilter::fromRequest([
            'name' => 'Widget',
            'minPrice' => '10.5',
            'maxPrice' => '99.99',
        ]);

        $this->assertEquals('Widget', $filter->getName());
        $this->assertEquals(10.5, $filter->getMinPrice());
        $this->assertEquals(99.99, $filter->getMaxPrice());
    }

    public function testFromRequestWithEmptyParams(): void
    {
        $filter = ProductFilter::fromRequest([]);

        $this->assertNull($filter->getName());
        $this->assertNull($filter->getMinPrice());
        $this->assertNull($filter->getMaxPrice());
    }

    public function testFromRequestWithPartialParams(): void
    {
        $filter = ProductFilter::fromRequest([
            'name' => 'Test',
        ]);

        $this->assertEquals('Test', $filter->getName());
        $this->assertNull($filter->getMinPrice());
        $this->assertNull($filter->getMaxPrice());
    }

    public function testFromRequestWithEmptyStringValues(): void
    {
        $filter = ProductFilter::fromRequest([
            'name' => '',
            'minPrice' => '',
            'maxPrice' => '',
        ]);

        $this->assertNull($filter->getName());
        $this->assertNull($filter->getMinPrice());
        $this->assertNull($filter->getMaxPrice());
    }

    public function testFromRequestCastsPriceStrings(): void
    {
        $filter = ProductFilter::fromRequest([
            'minPrice' => '25',
            'maxPrice' => '150',
        ]);

        $this->assertIsFloat($filter->getMinPrice());
        $this->assertIsFloat($filter->getMaxPrice());
        $this->assertEquals(25.0, $filter->getMinPrice());
        $this->assertEquals(150.0, $filter->getMaxPrice());
    }

    public function testConstructorWithDirectValues(): void
    {
        $filter = new ProductFilter(
            name: 'Direct',
            minPrice: 5.0,
            maxPrice: 50.0,
        );

        $this->assertEquals('Direct', $filter->getName());
        $this->assertEquals(5.0, $filter->getMinPrice());
        $this->assertEquals(50.0, $filter->getMaxPrice());
    }

    public function testConstructorDefaultsToNull(): void
    {
        $filter = new ProductFilter();

        $this->assertNull($filter->getName());
        $this->assertNull($filter->getMinPrice());
        $this->assertNull($filter->getMaxPrice());
    }
}
