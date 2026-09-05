<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entities\Product;
use App\Entities\ProductAttribute;
use App\Entities\ProductImage;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testCalculateDiscountWithValidPrices(): void
    {
        $product = new Product();
        $product->setPrice(100.0);
        $product->setPurchasePrice(60.0);
        $product->calculateDiscount();

        $this->assertEquals(40.0, $product->getDiscount());
    }

    public function testCalculateDiscountWithZeroPrice(): void
    {
        $product = new Product();
        $product->setPrice(0.0);
        $product->setPurchasePrice(60.0);
        $product->calculateDiscount();

        $this->assertNull($product->getDiscount());
    }

    public function testCalculateDiscountWithNullPurchasePrice(): void
    {
        $product = new Product();
        $product->setPrice(100.0);
        $product->setPurchasePrice(null);
        $product->calculateDiscount();

        $this->assertNull($product->getDiscount());
    }

    public function testCalculateDiscountRoundsToTwoDecimals(): void
    {
        $product = new Product();
        $product->setPrice(100.0);
        $product->setPurchasePrice(33.33);
        $product->calculateDiscount();

        $this->assertEquals(66.67, $product->getDiscount());
    }

    public function testToArrayReturnsCorrectShape(): void
    {
        $product = new Product();
        $product->setExternalCode('EXT-001');
        $product->setName('Test Product');
        $product->setDescription('Description');
        $product->setPrice(99.99);
        $product->setPurchasePrice(50.0);
        $product->calculateDiscount();

        $array = $product->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertEquals('EXT-001', $array['external_code']);
        $this->assertEquals('Test Product', $array['name']);
        $this->assertEquals('Description', $array['description']);
        $this->assertEquals(99.99, $array['price']);
        $this->assertEquals(50.0, $array['purchase_price']);
        $this->assertEquals(50.0, $array['discount']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertArrayHasKey('attributes', $array);
        $this->assertArrayHasKey('images', $array);
        $this->assertIsArray($array['attributes']);
        $this->assertIsArray($array['images']);
    }

    public function testToListItemArrayExcludesAttributesAndImages(): void
    {
        $product = new Product();
        $product->setExternalCode('EXT-001');
        $product->setName('Test Product');
        $product->setPrice(99.99);

        $attr = new ProductAttribute();
        $attr->setKey('Color');
        $attr->setValue('Red');
        $product->addAttribute($attr);

        $image = new ProductImage();
        $image->setUrl('http://example.com/img.jpg');
        $product->addImage($image);

        $array = $product->toListItemArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertEquals('EXT-001', $array['external_code']);
        $this->assertEquals('Test Product', $array['name']);
        $this->assertEquals(99.99, $array['price']);
        $this->assertEquals(1, $array['images_count']);
        $this->assertArrayNotHasKey('attributes', $array);
        $this->assertArrayNotHasKey('description', $array);
        $this->assertArrayNotHasKey('created_at', $array);
    }

    public function testAddAttributeBidirectional(): void
    {
        $product = new Product();
        $attr = new ProductAttribute();
        $attr->setKey('Material');
        $attr->setValue('Cotton');

        $product->addAttribute($attr);

        $this->assertTrue($product->getAttributes()->contains($attr));
        $this->assertSame($product, $attr->getProduct());
    }

    public function testAddAttributeDoesNotDuplicate(): void
    {
        $product = new Product();
        $attr = new ProductAttribute();
        $attr->setKey('Color');
        $attr->setValue('Blue');

        $product->addAttribute($attr);
        $product->addAttribute($attr);

        $this->assertCount(1, $product->getAttributes());
    }

    public function testAddImageBidirectional(): void
    {
        $product = new Product();
        $image = new ProductImage();
        $image->setUrl('http://example.com/photo.jpg');

        $product->addImage($image);

        $this->assertTrue($product->getImages()->contains($image));
        $this->assertSame($product, $image->getProduct());
    }

    public function testAddImageDoesNotDuplicate(): void
    {
        $product = new Product();
        $image = new ProductImage();
        $image->setUrl('http://example.com/photo.jpg');

        $product->addImage($image);
        $product->addImage($image);

        $this->assertCount(1, $product->getImages());
    }

    public function testSetUpdatedAtValue(): void
    {
        $product = new Product();
        $before = $product->getUpdatedAt();

        // Simulate time passing
        usleep(10000); // 10ms

        $product->setUpdatedAtValue();
        $after = $product->getUpdatedAt();

        $this->assertGreaterThan($before, $after);
    }

    public function testCreatedAtIsSetOnConstruct(): void
    {
        $product = new Product();
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getCreatedAt());
    }

    public function testDefaultValues(): void
    {
        $product = new Product();

        $this->assertNull($product->getDescription());
        $this->assertNull($product->getPurchasePrice());
        $this->assertNull($product->getDiscount());
        $this->assertCount(0, $product->getAttributes());
        $this->assertCount(0, $product->getImages());
    }
}
