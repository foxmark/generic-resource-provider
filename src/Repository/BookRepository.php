<?php
// src/Repository/BookRepository.php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Standard Doctrine repository.
 * Add custom query methods here; keep them out of the entity.
 *
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /** @return Book[] Available books ordered by title */
    public function findAvailable(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.available = :available')
            ->setParameter('available', true)
            ->orderBy('b.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
