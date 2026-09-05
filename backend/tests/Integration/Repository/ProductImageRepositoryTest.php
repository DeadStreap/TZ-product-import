<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entities\ProductImage;
use App\Tests\Integration\IntegrationTestCase;

class ProductImageRepositoryTest extends IntegrationTestCase
{
    public function testSaveAndFindByProduct(): void
    {
        $product = $this->createProduct();

        $image1 = new ProductImage();
        $image1->setProduct($product);
        $image1->setUrl('http://example.com/photo1.jpg');
        $image1->setPath('2026/09/photo1.jpg');
        $this->em->persist($image1);

        $image2 = new ProductImage();
        $image2->setProduct($product);
        $image2->setUrl('http://example.com/photo2.jpg');
        $image2->setPath('2026/09/photo2.jpg');
        $this->em->persist($image2);

        $this->em->flush();

        $images = $this->imageRepo->findByProduct($product->getId());

        $this->assertCount(2, $images);
        $this->assertEquals('http://example.com/photo1.jpg', $images[0]->getUrl());
        $this->assertEquals('http://example.com/photo2.jpg', $images[1]->getUrl());
    }

    public function testFindByProductReturnsEmptyArray(): void
    {
        $product = $this->createProduct();

        $images = $this->imageRepo->findByProduct($product->getId());

        $this->assertCount(0, $images);
    }

    public function testDeleteByProduct(): void
    {
        $product = $this->createProduct();

        $image1 = new ProductImage();
        $image1->setProduct($product);
        $image1->setUrl('http://example.com/img1.jpg');
        $this->em->persist($image1);

        $image2 = new ProductImage();
        $image2->setProduct($product);
        $image2->setUrl('http://example.com/img2.jpg');
        $this->em->persist($image2);

        $this->em->flush();

        $this->imageRepo->deleteByProduct($product->getId());
        $this->em->flush();

        $images = $this->imageRepo->findByProduct($product->getId());
        $this->assertCount(0, $images);
    }

    public function testDeleteByProductOnlyDeletesForThatProduct(): void
    {
        $product1 = $this->createProduct('EXT-001', 'Product 1');
        $product2 = $this->createProduct('EXT-002', 'Product 2');

        $image1 = new ProductImage();
        $image1->setProduct($product1);
        $image1->setUrl('http://example.com/img1.jpg');
        $this->em->persist($image1);

        $image2 = new ProductImage();
        $image2->setProduct($product2);
        $image2->setUrl('http://example.com/img2.jpg');
        $this->em->persist($image2);

        $this->em->flush();

        $this->imageRepo->deleteByProduct($product1->getId());
        $this->em->flush();

        $this->assertCount(0, $this->imageRepo->findByProduct($product1->getId()));
        $this->assertCount(1, $this->imageRepo->findByProduct($product2->getId()));
    }

    public function testSaveImageWithNullPath(): void
    {
        $product = $this->createProduct();

        $image = new ProductImage();
        $image->setProduct($product);
        $image->setUrl('http://example.com/pending.jpg');
        $image->setPath(null);
        $this->em->persist($image);
        $this->em->flush();

        $images = $this->imageRepo->findByProduct($product->getId());

        $this->assertCount(1, $images);
        $this->assertNull($images[0]->getPath());
        $this->assertEquals('http://example.com/pending.jpg', $images[0]->getUrl());
    }
}
