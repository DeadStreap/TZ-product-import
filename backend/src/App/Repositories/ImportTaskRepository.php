<?php

declare(strict_types=1);

namespace App\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use App\Entities\ImportTask;

class ImportTaskRepository
{
    private EntityRepository $repo;
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->repo = $em->getRepository(ImportTask::class);
    }

    public function find(int $id): ?ImportTask
    {
        return $this->repo->find($id);
    }

    public function save(ImportTask $task): void
    {
        $this->em->persist($task);
    }

    public function flush(): void
    {
        $this->em->flush();
    }
}
