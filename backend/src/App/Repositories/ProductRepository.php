<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\ProductFilter;
use App\Entities\Product;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class ProductRepository
{
    private EntityRepository $repo;
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->repo = $em->getRepository(Product::class);
    }

    public function find(int $id): ?Product
    {
        return $this->repo->find($id);
    }

    public function findByExternalCode(string $externalCode): ?Product
    {
        return $this->repo->findOneBy(['externalCode' => $externalCode]);
    }

    public function findByFilter(ProductFilter $filter, int $page, int $limit): array
    {
        $qb = $this->repo->createQueryBuilder('p');

        if ($filter->getName() !== null && $filter->getName() !== '') {
            $qb->andWhere('p.name LIKE :name')
               ->setParameter('name', '%' . $filter->getName() . '%');
        }

        if ($filter->getMinPrice() !== null) {
            $qb->andWhere('p.price >= :minPrice')
               ->setParameter('minPrice', $filter->getMinPrice());
        }

        if ($filter->getMaxPrice() !== null) {
            $qb->andWhere('p.price <= :maxPrice')
               ->setParameter('maxPrice', $filter->getMaxPrice());
        }

        $total = (int) (clone $qb)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        $products = $qb->orderBy('p.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $ids = array_map(fn(Product $p) => $p->getId(), $products);

        if ($ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $counts = $this->em->getConnection()->fetchAllAssociative(
                "SELECT product_id, COUNT(id) AS cnt FROM product_images WHERE product_id IN ($placeholders) GROUP BY product_id",
                $ids
            );

            foreach ($counts as $row) {
                $productId = (int) $row['product_id'];
                $count = (int) $row['cnt'];
                foreach ($products as $p) {
                    if ($p->getId() === $productId) {
                        $p->setImagesCount($count);
                        break;
                    }
                }
            }
        }

        return [
            'items' => $products,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => (int) ceil($total / $limit),
        ];
    }

    public function save(Product $product): void
    {
        $this->em->persist($product);
    }

    public function flush(): void
    {
        $this->em->flush();
    }

    public function count(): int
    {
        return $this->repo->count([]);
    }
}
