<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class UserRepository
{
    private EntityRepository $repo;
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->repo = $em->getRepository(User::class);
    }

    public function find(int $id): ?User
    {
        return $this->repo->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->repo->findOneBy(['email' => $email]);
    }

    public function save(User $user): void
    {
        $this->em->persist($user);
    }

    public function flush(): void
    {
        $this->em->flush();
    }
}
