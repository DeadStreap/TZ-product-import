<?php

declare(strict_types=1);

namespace App\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use App\Entities\Product;
use App\Entities\ProductImage;
use App\DTO\ProductFilter;

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

        $ids = array_map(fn (Product $p) => $p->getId(), $products);

        if ($ids !== []) {
            $counts = $this->em->createQueryBuilder()
                ->select('IDENTITY(i.product)', 'COUNT(i.id)')
                ->from(ProductImage::class, 'i')
                ->where('i.product IN (:ids)')
                ->setParameter('ids', $ids)
                ->groupBy('i.product')
                ->getQuery()
                ->getArrayResult();

            foreach ($counts as $row) {
                foreach ($products as $p) {
                    if ($p->getId() === (int) $row[0]) {
                        $p->setImagesCount((int) $row[1]);
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
