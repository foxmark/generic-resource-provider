<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BookRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private BookRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository = static::getContainer()->get(BookRepository::class);
        $this->em->createQuery('DELETE FROM App\Entity\Book b')->execute();
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Book b')->execute();
        parent::tearDown();
    }

    private function createBook(string $title, string $isbn, bool $available): Book
    {
        $book = new Book();
        $book->setTitle($title);
        $book->setIsbn($isbn);
        $book->setAuthorName('Test Author');
        $book->setAvailable($available);
        $this->em->persist($book);
        $this->em->flush();
        return $book;
    }

    public function testFindAvailableReturnsOnlyAvailableBooks(): void
    {
        $this->createBook('Alpha Book', '9780743273565', true);
        $this->createBook('Beta Book', '9780451524935', true);
        $this->createBook('Unavailable Book', '9780061965487', false);

        $results = $this->repository->findAvailable();

        $this->assertCount(2, $results);
    }

    public function testFindAvailableReturnsBooksInTitleAscOrder(): void
    {
        $this->createBook('Zebra Book', '9780743273565', true);
        $this->createBook('Alpha Book', '9780451524935', true);

        $results = $this->repository->findAvailable();

        $this->assertCount(2, $results);
        $this->assertSame('Alpha Book', $results[0]->getTitle());
        $this->assertSame('Zebra Book', $results[1]->getTitle());
    }

    public function testFindAvailableReturnsEmptyArrayWhenNoBooksAvailable(): void
    {
        $this->createBook('Unavailable Book', '9780743273565', false);

        $results = $this->repository->findAvailable();

        $this->assertSame([], $results);
    }
}
