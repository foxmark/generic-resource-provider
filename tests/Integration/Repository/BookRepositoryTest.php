<?php

/**
 * TASK-004: BookRepository — Integration Tests
 *
 * Phase: RED — integration tests using a real test database.
 * Each test is wrapped in a transaction that is rolled back in tearDown
 * so tests remain independent of each other.
 *
 * Prerequisites:
 *   docker compose exec php symfony console doctrine:database:create --env=test --if-not-exists
 *   docker compose exec php symfony console doctrine:migrations:migrate --env=test --no-interaction
 *
 * @group red
 */

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\DefaultOrderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for BookRepository.
 *
 * Covered contract points:
 *  - findAvailable() returns only available books (not unavailable ones)
 *  - findAvailable() returns results ordered by title ASC
 *  - findAvailable() returns empty array when no books are available
 *  - getDefaultOrder() returns ['id' => 'ASC']
 *  - BookRepository implements DefaultOrderRepositoryInterface
 */
class BookRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private BookRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->em->getRepository(Book::class);

        // Wrap each test in a transaction; rolled back in tearDown for isolation.
        $this->em->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function createBook(
        string $title,
        string $isbn,
        string $authorName = 'Test Author',
        bool $available = true,
    ): Book {
        $book = new Book();
        $book->setTitle($title);
        $book->setIsbn($isbn);
        $book->setAuthorName($authorName);
        $book->setAvailable($available);

        $this->em->persist($book);
        $this->em->flush();

        return $book;
    }

    // -------------------------------------------------------------------------
    // Repository contract: implements DefaultOrderRepositoryInterface
    // -------------------------------------------------------------------------

    public function testRepositoryImplementsDefaultOrderRepositoryInterface(): void
    {
        $this->assertInstanceOf(DefaultOrderRepositoryInterface::class, $this->repository);
    }

    // -------------------------------------------------------------------------
    // getDefaultOrder()
    // -------------------------------------------------------------------------

    public function testGetDefaultOrderReturnsIdAsc(): void
    {
        $order = $this->repository->getDefaultOrder();

        $this->assertSame(['id' => 'ASC'], $order);
    }

    // -------------------------------------------------------------------------
    // findAvailable()
    // -------------------------------------------------------------------------

    public function testFindAvailableReturnsOnlyAvailableBooks(): void
    {
        $available1   = $this->createBook('Available A', '978-0132350884', 'Author A', true);
        $available2   = $this->createBook('Available B', '978-0201485677', 'Author B', true);
        $unavailable  = $this->createBook('Unavailable',  '978-0201633610', 'Author C', false);

        $results = $this->repository->findAvailable();

        $ids = array_map(fn(Book $b) => $b->getId(), $results);
        $this->assertContains($available1->getId(), $ids);
        $this->assertContains($available2->getId(), $ids);
        $this->assertNotContains($unavailable->getId(), $ids);
    }

    public function testFindAvailableDoesNotReturnUnavailableBooks(): void
    {
        $this->createBook('Unavailable One', '978-0132350884', 'Author', false);
        $this->createBook('Unavailable Two', '978-0201485677', 'Author', false);

        $results = $this->repository->findAvailable();

        $this->assertEmpty($results);
    }

    public function testFindAvailableReturnsEmptyArrayWhenNoBooksAvailable(): void
    {
        // No books seeded at all.
        $results = $this->repository->findAvailable();

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testFindAvailableOrdersByTitleAscending(): void
    {
        // Seed out of alphabetical order to verify sorting is applied by the query.
        $this->createBook('Zebra Book',  '978-0132350884', 'Author Z', true);
        $this->createBook('Alpha Book',  '978-0201485677', 'Author A', true);
        $this->createBook('Middle Book', '978-0201633610', 'Author M', true);

        $results = $this->repository->findAvailable();

        $this->assertCount(3, $results);
        $this->assertSame('Alpha Book',  $results[0]->getTitle());
        $this->assertSame('Middle Book', $results[1]->getTitle());
        $this->assertSame('Zebra Book',  $results[2]->getTitle());
    }

    public function testFindAvailableReturnsBooksAsBookEntities(): void
    {
        $this->createBook('Some Book', '978-0132350884', 'Author', true);

        $results = $this->repository->findAvailable();

        $this->assertNotEmpty($results);
        $this->assertContainsOnlyInstancesOf(Book::class, $results);
    }

    public function testFindAvailableCountMatchesOnlyAvailableBooks(): void
    {
        $this->createBook('Avail 1',  '978-0132350884', 'Author', true);
        $this->createBook('Avail 2',  '978-0201485677', 'Author', true);
        $this->createBook('Unavail',  '978-0201633610', 'Author', false);

        $results = $this->repository->findAvailable();

        $this->assertCount(2, $results);
    }
}
