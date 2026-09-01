<?php

declare(strict_types=1);

namespace App\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use App\Entities\ProductImage;

class ProductImageRepository
{
    private EntityRepository $repo;
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->repo = $em->getRepository(ProductImage::class);
    }

    /** @return ProductImage[] */
    public function findByProduct(int $productId): array
    {
        return $this->repo->findBy(['product' => $productId]);
    }

    public function save(ProductImage $image): void
    {
        $this->em->persist($image);
    }

    public function deleteByProduct(int $productId): void
    {
        $images = $this->findByProduct($productId);
        foreach ($images as $img) {
            $this->em->remove($img);
        }
    }
}
