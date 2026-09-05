<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entities\ProductAttribute;
use App\Tests\Integration\IntegrationTestCase;

class ProductAttributeRepositoryTest extends IntegrationTestCase
{
    public function testSaveAndFindByProduct(): void
    {
        $product = $this->createProduct();

        $attr1 = new ProductAttribute();
        $attr1->setProduct($product);
        $attr1->setKey('Color');
        $attr1->setValue('Red');
        $this->em->persist($attr1);

        $attr2 = new ProductAttribute();
        $attr2->setProduct($product);
        $attr2->setKey('Size');
        $attr2->setValue('Large');
        $this->em->persist($attr2);

        $this->em->flush();

        $attributes = $this->attributeRepo->findByProduct($product->getId());

        $this->assertCount(2, $attributes);
        $this->assertEquals('Color', $attributes[0]->getKey());
        $this->assertEquals('Red', $attributes[0]->getValue());
        $this->assertEquals('Size', $attributes[1]->getKey());
        $this->assertEquals('Large', $attributes[1]->getValue());
    }

    public function testFindByProductReturnsEmptyArray(): void
    {
        $product = $this->createProduct();

        $attributes = $this->attributeRepo->findByProduct($product->getId());

        $this->assertCount(0, $attributes);
    }

    public function testDeleteByProduct(): void
    {
        $product = $this->createProduct();

        $attr1 = new ProductAttribute();
        $attr1->setProduct($product);
        $attr1->setKey('Material');
        $attr1->setValue('Cotton');
        $this->em->persist($attr1);

        $attr2 = new ProductAttribute();
        $attr2->setProduct($product);
        $attr2->setKey('Weight');
        $attr2->setValue('200g');
        $this->em->persist($attr2);

        $this->em->flush();

        // Delete all attributes for this product
        $this->attributeRepo->deleteByProduct($product->getId());
        $this->em->flush();

        $attributes = $this->attributeRepo->findByProduct($product->getId());
        $this->assertCount(0, $attributes);
    }

    public function testDeleteByProductOnlyDeletesForThatProduct(): void
    {
        $product1 = $this->createProduct('EXT-001', 'Product 1');
        $product2 = $this->createProduct('EXT-002', 'Product 2');

        $attr1 = new ProductAttribute();
        $attr1->setProduct($product1);
        $attr1->setKey('Color');
        $attr1->setValue('Red');
        $this->em->persist($attr1);

        $attr2 = new ProductAttribute();
        $attr2->setProduct($product2);
        $attr2->setKey('Color');
        $attr2->setValue('Blue');
        $this->em->persist($attr2);

        $this->em->flush();

        // Delete only product1's attributes
        $this->attributeRepo->deleteByProduct($product1->getId());
        $this->em->flush();

        // product1 should have no attributes
        $this->assertCount(0, $this->attributeRepo->findByProduct($product1->getId()));

        // product2 should still have its attribute
        $this->assertCount(1, $this->attributeRepo->findByProduct($product2->getId()));
    }

    public function testSaveAttributeWithNullValue(): void
    {
        $product = $this->createProduct();

        $attr = new ProductAttribute();
        $attr->setProduct($product);
        $attr->setKey('Barcode');
        $attr->setValue(null);
        $this->em->persist($attr);
        $this->em->flush();

        $attributes = $this->attributeRepo->findByProduct($product->getId());

        $this->assertCount(1, $attributes);
        $this->assertNull($attributes[0]->getValue());
    }
}
