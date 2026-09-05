<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entities\Product;
use App\Entities\ProductImage;
use PHPUnit\Framework\TestCase;

class ProductImageTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $image = new ProductImage();
        $image->setUrl('http://example.com/photo.jpg');
        $image->setPath('2026/09/abc123.jpg');

        $this->assertEquals('http://example.com/photo.jpg', $image->getUrl());
        $this->assertEquals('2026/09/abc123.jpg', $image->getPath());
    }

    public function testNullablePath(): void
    {
        $image = new ProductImage();
        $image->setUrl('http://example.com/photo.jpg');
        $image->setPath(null);

        $this->assertEquals('http://example.com/photo.jpg', $image->getUrl());
        $this->assertNull($image->getPath());
    }

    public function testSetProduct(): void
    {
        $product = new Product();
        $image = new ProductImage();
        $image->setProduct($product);

        $this->assertSame($product, $image->getProduct());
    }

    public function testToArray(): void
    {
        $image = new ProductImage();
        $image->setUrl('http://example.com/photo.jpg');
        $image->setPath('2026/09/img.jpg');

        $array = $image->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertEquals('http://example.com/photo.jpg', $array['url']);
        $this->assertEquals('2026/09/img.jpg', $array['path']);
    }

    public function testToArrayWithNullPath(): void
    {
        $image = new ProductImage();
        $image->setUrl('http://example.com/photo.jpg');
        $image->setPath(null);

        $array = $image->toArray();

        $this->assertEquals('http://example.com/photo.jpg', $array['url']);
        $this->assertNull($array['path']);
    }
}
