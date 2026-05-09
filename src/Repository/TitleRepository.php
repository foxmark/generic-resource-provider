<?php

namespace App\Repository;

use App\Entity\Title;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Title>
 */
class TitleRepository extends ServiceEntityRepository implements DefaultOrderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Title::class);
    }

    public function getDefaultOrder(): array
    {
        return ['title' => 'ASC'];
    }
}
