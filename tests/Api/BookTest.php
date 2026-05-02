<?php

/**
 * TASK-001: Book API — Integration Tests (HTTP endpoints)
 *
 * Phase: RED — these tests define the API contract and must fail before
 * any implementation is in place (or while the implementation is incomplete).
 *
 * @group red
 */

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * HTTP integration tests for all six BookResource endpoints.
 *
 * Covered API contract points:
 *  - GET  /api/books            — collection, pagination, empty list
 *  - GET  /api/books/{id}       — single item, field shape, 404
 *  - POST /api/books            — create, 201 + Location, validation (422), uniqueness (422)
 *  - PUT  /api/books/{id}       — full replace, 200, 404
 *  - PATCH /api/books/{id}      — partial update, 200, 404
 *  - DELETE /api/books/{id}     — 204, non-existent 404
 */
class BookTest extends ApiTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = static::bootKernel()->getContainer()
            ->get('doctrine')
            ->getManager();

        // Truncate the book table so each test starts from a clean slate.
        // Transaction rollback cannot be used here because ApiTestCase::createClient()
        // reboots the kernel (and thus opens a new DB connection), so the HTTP layer
        // never sees the outer transaction started in setUp.
        $this->em->getConnection()->executeStatement('DELETE FROM book');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Persist a Book entity directly (bypasses the HTTP layer so tests can seed data fast).
     */
    private function createBook(
        string $title = 'Clean Code',
        string $isbn = '978-0132350884',
        string $authorName = 'Robert C. Martin',
        ?int $publicationYear = 2008,
        bool $available = true,
    ): Book {
        $book = new Book();
        $book->setTitle($title);
        $book->setIsbn($isbn);
        $book->setAuthorName($authorName);
        $book->setAvailable($available);

        if ($publicationYear !== null) {
            $book->setPublishedAt(new \DateTimeImmutable(sprintf('%d-01-01', $publicationYear)));
        }

        // Trigger @PrePersist lifecycle callback
        $this->em->persist($book);
        $this->em->flush();

        return $book;
    }

    private function makeClient(): \ApiPlatform\Symfony\Bundle\Test\Client
    {
        return static::createClient([], ['base_uri' => 'http://localhost']);
    }

    // -------------------------------------------------------------------------
    // GET /api/books — collection
    // -------------------------------------------------------------------------

    public function testGetBookCollectionReturnsEmptyList(): void
    {
        $client = $this->makeClient();

        $response = $client->request('GET', '/api/books');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context'   => '/api/contexts/Book',
            '@type'      => 'Collection',
            'member'     => [],
            'totalItems' => 0,
        ]);
    }

    public function testGetBookCollectionReturnsPaginatedList(): void
    {
        $this->createBook('Alpha', '978-0132350884', 'Author A', 2001);
        $this->createBook('Beta',  '978-0201633610', 'Author B', 2002);

        $client = $this->makeClient();
        $client->request('GET', '/api/books');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'totalItems' => 2,
        ]);

        $data = $client->getResponse()->toArray();
        $this->assertCount(2, $data['member']);
    }

    public function testGetBookCollectionDefaultsTo10ItemsPerPage(): void
    {
        // Seed 12 books and confirm the response only contains 10 by default.
        $isbns = [
            '978-0132350884', '978-0201633610', '978-0596516178', '978-0131101630',
            '978-0201485677', '978-0321125217', '978-0201633344', '978-0596007126',
            '978-0321127426', '978-0132760571', '978-1491950357', '978-0134494166',
        ];
        foreach ($isbns as $i => $isbn) {
            $this->createBook("Book {$i}", $isbn, "Author {$i}", 2000 + $i);
        }

        $client = $this->makeClient();
        $client->request('GET', '/api/books');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertCount(10, $data['member']);
        $this->assertSame(12, $data['totalItems']);
    }

    public function testGetBookCollectionClientCanRequestDifferentPageSize(): void
    {
        $isbns = [
            '978-0132350884', '978-0201633610', '978-0596516178', '978-0131101630',
            '978-0201485677', '978-0321125217', '978-0201633344', '978-0596007126',
        ];
        foreach ($isbns as $i => $isbn) {
            $this->createBook("Book {$i}", $isbn, "Author {$i}", 2000 + $i);
        }

        $client = $this->makeClient();
        $client->request('GET', '/api/books?itemsPerPage=3');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertCount(3, $data['member']);
    }

    // -------------------------------------------------------------------------
    // GET /api/books/{id} — single item
    // -------------------------------------------------------------------------

    public function testGetSingleBookReturnsCorrectShape(): void
    {
        $book = $this->createBook('Clean Code', '978-0132350884', 'Robert C. Martin', 2008, true);

        $client = $this->makeClient();
        $client->request('GET', '/api/books/' . $book->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@type'           => 'Book',
            'id'              => $book->getId(),
            'title'           => 'Clean Code',
            'isbn'            => '978-0132350884',
            'authorName'      => 'Robert C. Martin',
            'publicationYear' => 2008,
            'available'       => true,
        ]);
    }

    public function testGetNonExistentBookReturns404(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/api/books/99999999');

        $this->assertResponseStatusCodeSame(404);
    }

    // -------------------------------------------------------------------------
    // POST /api/books — create
    // -------------------------------------------------------------------------

    public function testCreateBookReturns201WithLocation(): void
    {
        $client = $this->makeClient();
        $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'           => 'The Pragmatic Programmer',
                'isbn'            => '978-0201616224',
                'authorName'      => 'Andrew Hunt',
                'publicationYear' => 1999,
                'available'       => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHasHeader('Location');
        $this->assertJsonContains([
            '@type'           => 'Book',
            'title'           => 'The Pragmatic Programmer',
            'isbn'            => '978-0201616224',
            'authorName'      => 'Andrew Hunt',
            'publicationYear' => 1999,
            'available'       => true,
        ]);
    }

    public function testCreateBookPersistsData(): void
    {
        $client = $this->makeClient();
        $response = $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'      => 'Domain-Driven Design',
                'isbn'       => '978-0321125217',
                'authorName' => 'Eric Evans',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $location = $response->getHeaders()['location'][0];

        // Fetch by the returned Location header and verify persistence.
        $client->request('GET', $location);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'title'      => 'Domain-Driven Design',
            'isbn'       => '978-0321125217',
            'authorName' => 'Eric Evans',
        ]);
    }

    // -------------------------------------------------------------------------
    // POST validation failures → 422
    // -------------------------------------------------------------------------

    public function testCreateBookWithBlankTitleReturns422(): void
    {
        $client = $this->makeClient();
        $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'      => '',
                'isbn'       => '978-0201616224',
                'authorName' => 'Some Author',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            '@type'      => 'ConstraintViolation',
            'violations' => [
                ['propertyPath' => 'title', 'message' => 'This value should not be blank.'],
            ],
        ]);
    }

    public function testCreateBookWithTitleTooShortReturns422(): void
    {
        $client = $this->makeClient();
        $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'      => 'X',
                'isbn'       => '978-0201616224',
                'authorName' => 'Some Author',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['@type' => 'ConstraintViolation']);
    }

    public function testCreateBookWithTitleTooLongReturns422(): void
    {
        $client = $this->makeClient();
        $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'      => str_repeat('A', 256),
                'isbn'       => '978-0201616224',
                'authorName' => 'Some Author',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['@type' => 'ConstraintViolation']);
    }

    public function testCreateBookWithBlankIsbnReturns422(): void
    {
        $client = $this->makeClient();
        $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'      => 'Valid Title',
                'isbn'       => '',
                'authorName' => 'Some Author',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['@type' => 'ConstraintViolation']);
    }

    public function testCreateBookWithInvalidIsbnReturns422(): void
    {
        $client = $this->makeClient();
        $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'      => 'Valid Title',
                'isbn'       => 'NOT-AN-ISBN',
                'authorName' => 'Some Author',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['@type' => 'ConstraintViolation']);
    }

    public function testCreateBookWithBlankAuthorNameReturns422(): void
    {
        $client = $this->makeClient();
        $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'      => 'Valid Title',
                'isbn'       => '978-0201616224',
                'authorName' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['@type' => 'ConstraintViolation']);
    }

    public function testCreateBookWithPublicationYearBelowRangeReturns422(): void
    {
        $client = $this->makeClient();
        $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'           => 'Valid Title',
                'isbn'            => '978-0201616224',
                'authorName'      => 'Some Author',
                'publicationYear' => 999,
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['@type' => 'ConstraintViolation']);
    }

    public function testCreateBookWithPublicationYearAboveRangeReturns422(): void
    {
        $client = $this->makeClient();
        $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'           => 'Valid Title',
                'isbn'            => '978-0201616224',
                'authorName'      => 'Some Author',
                'publicationYear' => 2101,
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['@type' => 'ConstraintViolation']);
    }

    public function testIsbnUniqueConstraintReturns422(): void
    {
        // Requires #[UniqueEntity(fields: ['isbn'])] on Book entity (confirmed present).
        $this->createBook('First Book', '978-0132350884', 'Author One', 2001);

        $client = $this->makeClient();
        $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'      => 'Different Title Same ISBN',
                'isbn'       => '978-0132350884',
                'authorName' => 'Author Two',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['@type' => 'ConstraintViolation']);
    }

    // -------------------------------------------------------------------------
    // PUT /api/books/{id} — full replace
    // -------------------------------------------------------------------------

    public function testReplaceBookWithPutReturns200(): void
    {
        $book = $this->createBook('Old Title', '978-0132350884', 'Old Author', 2000, true);

        $client = $this->makeClient();
        $client->request('PUT', '/api/books/' . $book->getId(), [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'           => 'New Title',
                'isbn'            => '978-0132350884',
                'authorName'      => 'New Author',
                'publicationYear' => 2020,
                'available'       => false,
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'title'           => 'New Title',
            'authorName'      => 'New Author',
            'publicationYear' => 2020,
            'available'       => false,
        ]);
    }

    public function testPutNonExistentBookReturns404(): void
    {
        $client = $this->makeClient();
        $client->request('PUT', '/api/books/99999999', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'      => 'Title',
                'isbn'       => '978-0132350884',
                'authorName' => 'Author',
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/books/{id} — partial update
    // -------------------------------------------------------------------------

    public function testPartialUpdateBookWithPatchReturns200(): void
    {
        $book = $this->createBook('Original Title', '978-0132350884', 'Original Author', 2000, true);

        $client = $this->makeClient();
        $client->request('PATCH', '/api/books/' . $book->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json'    => [
                'title' => 'Patched Title',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'title'      => 'Patched Title',
            'authorName' => 'Original Author', // unchanged
            'isbn'       => '978-0132350884',  // unchanged
        ]);
    }

    public function testPatchNonExistentBookReturns404(): void
    {
        $client = $this->makeClient();
        $client->request('PATCH', '/api/books/99999999', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json'    => ['title' => 'Anything'],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/books/{id}
    // -------------------------------------------------------------------------

    public function testDeleteBookReturns204(): void
    {
        $book = $this->createBook('To Delete', '978-0132350884', 'Author', 2000);

        $client = $this->makeClient();
        $client->request('DELETE', '/api/books/' . $book->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeletedBookReturns404(): void
    {
        $book = $this->createBook('To Delete', '978-0132350884', 'Author', 2000);
        $id   = $book->getId();

        $client = $this->makeClient();
        $client->request('DELETE', '/api/books/' . $id);
        $this->assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/books/' . $id);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteNonExistentBookReturns404(): void
    {
        $client = $this->makeClient();
        $client->request('DELETE', '/api/books/99999999');

        $this->assertResponseStatusCodeSame(404);
    }

    // -------------------------------------------------------------------------
    // Field mapping / edge cases
    // -------------------------------------------------------------------------

    public function testPublicationYearNullWhenNotProvided(): void
    {
        $client = $this->makeClient();
        $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'      => 'No Year Book',
                'isbn'       => '978-0201616224',
                'authorName' => 'Some Author',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $client->getResponse()->toArray();
        $this->assertNull($data['publicationYear']);
    }

    public function testAvailableDefaultsToTrue(): void
    {
        $client = $this->makeClient();
        $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'      => 'No Available Field Book',
                'isbn'       => '978-0201616224',
                'authorName' => 'Some Author',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['available' => true]);
    }

    public function testPublicationYearRoundTrip(): void
    {
        // POST year 2001 → stored as 2001-01-01 → GET returns publicationYear 2001.
        // Day and month are silently discarded by BookMapper (by design — see ADR candidate in TASK-001).
        $client = $this->makeClient();
        $response = $client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json'    => [
                'title'           => 'Year Test Book',
                'isbn'            => '978-0201616224',
                'authorName'      => 'Some Author',
                'publicationYear' => 2001,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $location = $response->getHeaders()['location'][0];

        $client->request('GET', $location);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['publicationYear' => 2001]);
    }
}
