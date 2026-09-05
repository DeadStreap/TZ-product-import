<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\ProductAttribute;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class ProductAttributeRepository
{
    private EntityRepository $repo;
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->repo = $em->getRepository(ProductAttribute::class);
    }

    /** @return ProductAttribute[] */
    public function findByProduct(int $productId): array
    {
        return $this->repo->findBy(['product' => $productId]);
    }

    public function save(ProductAttribute $attribute): void
    {
        $this->em->persist($attribute);
    }

    public function deleteByProduct(int $productId): void
    {
        $attributes = $this->findByProduct($productId);
        foreach ($attributes as $attr) {
            $this->em->remove($attr);
        }
    }
}
