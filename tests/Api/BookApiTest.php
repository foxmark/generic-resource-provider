<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;

class BookApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private Client $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->em->createQuery('DELETE FROM App\Entity\Book b')->execute();
    }

    private function createBook(array $override = []): Book
    {
        $book = new Book();
        $book->setTitle($override['title'] ?? 'Test Title');
        $book->setIsbn($override['isbn'] ?? '9780743273565');
        $book->setAuthorName($override['authorName'] ?? 'Test Author');
        if (isset($override['publicationYear'])) {
            $book->setPublishedAt(new \DateTimeImmutable($override['publicationYear'] . '-01-01'));
        }
        $book->setAvailable($override['available'] ?? true);
        $this->em->persist($book);
        $this->em->flush();
        return $book;
    }

    // --- Endpoint definition tests ---

    public function testGetCollectionEndpointIsDefined(): void
    {
        $this->client->request('GET', '/api/books');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testGetSingleBookEndpointIsDefined(): void
    {
        $book = $this->createBook();

        $this->client->request('GET', '/api/books/' . $book->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testGetNonExistentBookReturns404(): void
    {
        $this->client->request('GET', '/api/books/99999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPostEndpointCreatesBookAndReturns201(): void
    {
        $this->client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => 'The Great Gatsby',
                'isbn' => '9780743273565',
                'authorName' => 'F. Scott Fitzgerald',
                'publicationYear' => 1925,
                'available' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testPutEndpointUpdatesBookAndReturns200(): void
    {
        $book = $this->createBook();

        $this->client->request('PUT', '/api/books/' . $book->getId(), [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => 'Updated Title',
                'isbn' => '9780743273565',
                'authorName' => 'Updated Author',
                'publicationYear' => 2000,
                'available' => false,
            ],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testPatchEndpointUpdatesBookAndReturns200(): void
    {
        $book = $this->createBook();

        $this->client->request('PATCH', '/api/books/' . $book->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['title' => 'Patched Title'],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteEndpointRemovesBookAndReturns204(): void
    {
        $book = $this->createBook();

        $this->client->request('DELETE', '/api/books/' . $book->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    // --- Validation tests ---

    private function post(array $data): void
    {
        $this->client->request('POST', '/api/books', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => $data,
        ]);
    }

    public function testTitleIsRequiredReturns422(): void
    {
        $this->post(['isbn' => '9780743273565', 'authorName' => 'F. Scott Fitzgerald']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testTitleTooShortReturns422(): void
    {
        $this->post(['title' => 'A', 'isbn' => '9780743273565', 'authorName' => 'F. Scott Fitzgerald']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testTitleTooLongReturns422(): void
    {
        $this->post(['title' => str_repeat('A', 256), 'isbn' => '9780743273565', 'authorName' => 'F. Scott Fitzgerald']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testIsbnIsRequiredReturns422(): void
    {
        $this->post(['title' => 'Valid Title', 'authorName' => 'F. Scott Fitzgerald']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testInvalidIsbnReturns422(): void
    {
        $this->post(['title' => 'Valid Title', 'isbn' => 'not-an-isbn', 'authorName' => 'F. Scott Fitzgerald']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAuthorNameIsRequiredReturns422(): void
    {
        $this->post(['title' => 'Valid Title', 'isbn' => '9780743273565']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testPublicationYearBelowMinReturns422(): void
    {
        $this->post(['title' => 'Valid Title', 'isbn' => '9780743273565', 'authorName' => 'F. Scott Fitzgerald', 'publicationYear' => 999]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testPublicationYearAboveMaxReturns422(): void
    {
        $this->post(['title' => 'Valid Title', 'isbn' => '9780743273565', 'authorName' => 'F. Scott Fitzgerald', 'publicationYear' => 2101]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testPutNonExistentBookReturns404(): void
    {
        $this->client->request('PUT', '/api/books/99999', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => 'Ghost Title',
                'isbn' => '9780743273565',
                'authorName' => 'Ghost Author',
                'publicationYear' => 2000,
                'available' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchNonExistentBookReturns404(): void
    {
        $this->client->request('PATCH', '/api/books/99999', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['title' => 'Ghost Patch'],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }
}
