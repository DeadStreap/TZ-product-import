<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entities\Product;
use App\Entities\ProductAttribute;
use PHPUnit\Framework\TestCase;

class ProductAttributeTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $attr = new ProductAttribute();
        $attr->setKey('Color');
        $attr->setValue('Red');

        $this->assertEquals('Color', $attr->getKey());
        $this->assertEquals('Red', $attr->getValue());
    }

    public function testNullableValue(): void
    {
        $attr = new ProductAttribute();
        $attr->setKey('Weight');
        $attr->setValue(null);

        $this->assertEquals('Weight', $attr->getKey());
        $this->assertNull($attr->getValue());
    }

    public function testSetProduct(): void
    {
        $product = new Product();
        $attr = new ProductAttribute();
        $attr->setProduct($product);

        $this->assertSame($product, $attr->getProduct());
    }

    public function testToArray(): void
    {
        $attr = new ProductAttribute();
        $attr->setKey('Material');
        $attr->setValue('Silk');

        $array = $attr->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertEquals('Material', $array['key']);
        $this->assertEquals('Silk', $array['value']);
    }

    public function testToArrayWithNullValue(): void
    {
        $attr = new ProductAttribute();
        $attr->setKey('Size');
        $attr->setValue(null);

        $array = $attr->toArray();

        $this->assertEquals('Size', $array['key']);
        $this->assertNull($array['value']);
    }
}
