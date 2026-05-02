<?php

/**
 * TASK-002: BookMapper — Unit Tests
 *
 * Phase: RED — pure unit tests, no Symfony kernel, no database.
 * Tests lock in the two-way mapping contract between Book (entity)
 * and BookResource (DTO).
 *
 * @group red
 */

declare(strict_types=1);

namespace App\Tests\Unit\Mapper;

use App\ApiResource\BookResource;
use App\Entity\Book;
use App\Mapper\BookMapper;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BookMapper.
 *
 * Covered contract points:
 *  - toResource(Book): BookResource  — all seven fields
 *  - toEntity(BookResource, ?Book): Book — all fields, new + existing entity
 *  - getSupportedResourceClass() / getSupportedEntityClass()
 *  - publicationYear ↔ publishedAt round-trip (lossy: day/month discarded by design)
 */
class BookMapperTest extends TestCase
{
    private BookMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new BookMapper();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a fully-populated Book entity without persisting it.
     * Uses reflection to set the private $id field.
     */
    private function buildBook(
        int $id = 1,
        string $title = 'Clean Code',
        string $isbn = '978-0132350884',
        string $authorName = 'Robert C. Martin',
        ?DateTimeImmutable $publishedAt = null,
        bool $available = true,
    ): Book {
        $book = new Book();
        $book->setTitle($title);
        $book->setIsbn($isbn);
        $book->setAuthorName($authorName);
        $book->setPublishedAt($publishedAt);
        $book->setAvailable($available);

        // Use reflection to inject a private id (entity is not persisted in unit tests).
        $ref = new \ReflectionClass($book);
        $prop = $ref->getProperty('id');
        $prop->setValue($book, $id);

        return $book;
    }

    private function buildResource(
        string $title = 'Clean Code',
        string $isbn = '978-0132350884',
        string $authorName = 'Robert C. Martin',
        ?int $publicationYear = 2008,
        bool $available = true,
    ): BookResource {
        $resource = new BookResource();
        $resource->title           = $title;
        $resource->isbn            = $isbn;
        $resource->authorName      = $authorName;
        $resource->publicationYear = $publicationYear;
        $resource->available       = $available;

        return $resource;
    }

    // -------------------------------------------------------------------------
    // toResource(Book): BookResource
    // -------------------------------------------------------------------------

    public function testToResourceMapsIdCorrectly(): void
    {
        $book = $this->buildBook(id: 42);

        $resource = $this->mapper->toResource($book);

        $this->assertSame(42, $resource->id);
    }

    public function testToResourceMapsTitleCorrectly(): void
    {
        $book = $this->buildBook(title: 'Refactoring');

        $resource = $this->mapper->toResource($book);

        $this->assertSame('Refactoring', $resource->title);
    }

    public function testToResourceMapsIsbnCorrectly(): void
    {
        $book = $this->buildBook(isbn: '978-0201485677');

        $resource = $this->mapper->toResource($book);

        $this->assertSame('978-0201485677', $resource->isbn);
    }

    public function testToResourceMapsAuthorNameCorrectly(): void
    {
        $book = $this->buildBook(authorName: 'Martin Fowler');

        $resource = $this->mapper->toResource($book);

        $this->assertSame('Martin Fowler', $resource->authorName);
    }

    public function testToResourceMapsAvailableCorrectly(): void
    {
        $bookAvailable   = $this->buildBook(available: true);
        $bookUnavailable = $this->buildBook(available: false);

        $this->assertTrue($this->mapper->toResource($bookAvailable)->available);
        $this->assertFalse($this->mapper->toResource($bookUnavailable)->available);
    }

    public function testToResourceMapsPublicationYearFromPublishedAt(): void
    {
        // publishedAt = 2001-06-15 → publicationYear = 2001 (day and month ignored)
        $book = $this->buildBook(publishedAt: new DateTimeImmutable('2001-06-15'));

        $resource = $this->mapper->toResource($book);

        $this->assertSame(2001, $resource->publicationYear);
    }

    public function testToResourcePublicationYearIsNullWhenPublishedAtIsNull(): void
    {
        $book = $this->buildBook(publishedAt: null);

        $resource = $this->mapper->toResource($book);

        $this->assertNull($resource->publicationYear);
    }

    // -------------------------------------------------------------------------
    // toEntity(BookResource, ?Book): Book
    // -------------------------------------------------------------------------

    public function testToEntityCreatesNewBookWhenNoExistingEntityGiven(): void
    {
        $resource = $this->buildResource();

        $book = $this->mapper->toEntity($resource);

        $this->assertInstanceOf(Book::class, $book);
    }

    public function testToEntitySetsTitle(): void
    {
        $resource = $this->buildResource(title: 'Design Patterns');

        $book = $this->mapper->toEntity($resource);

        $this->assertSame('Design Patterns', $book->getTitle());
    }

    public function testToEntitySetsIsbn(): void
    {
        $resource = $this->buildResource(isbn: '978-0201633610');

        $book = $this->mapper->toEntity($resource);

        $this->assertSame('978-0201633610', $book->getIsbn());
    }

    public function testToEntitySetsAuthorName(): void
    {
        $resource = $this->buildResource(authorName: 'Gang of Four');

        $book = $this->mapper->toEntity($resource);

        $this->assertSame('Gang of Four', $book->getAuthorName());
    }

    public function testToEntitySetsAvailable(): void
    {
        $resourceTrue  = $this->buildResource(available: true);
        $resourceFalse = $this->buildResource(available: false);

        $this->assertTrue($this->mapper->toEntity($resourceTrue)->isAvailable());
        $this->assertFalse($this->mapper->toEntity($resourceFalse)->isAvailable());
    }

    public function testToEntitySetsPublishedAtFromPublicationYear(): void
    {
        // publicationYear = 2001 → publishedAt = DateTimeImmutable('2001-01-01')
        // NOTE: BookMapper deliberately stores {year}-01-01; the original day/month
        // are discarded. This is a known lossy mapping — see ADR candidate noted in TASK-001.
        $resource = $this->buildResource(publicationYear: 2001);

        $book = $this->mapper->toEntity($resource);

        $this->assertNotNull($book->getPublishedAt());
        $this->assertSame('2001-01-01', $book->getPublishedAt()->format('Y-m-d'));
    }

    public function testToEntityPublishedAtIsNullWhenPublicationYearIsNull(): void
    {
        $resource = $this->buildResource(publicationYear: null);

        $book = $this->mapper->toEntity($resource);

        $this->assertNull($book->getPublishedAt());
    }

    public function testToEntityUpdatesExistingBookWhenEntityIsProvided(): void
    {
        $existing = $this->buildBook(title: 'Old Title', isbn: '978-0132350884', authorName: 'Old Author');
        $resource = $this->buildResource(title: 'New Title', isbn: '978-0132350884', authorName: 'New Author');

        $returned = $this->mapper->toEntity($resource, $existing);

        // Must mutate and return the same object, not create a new one.
        $this->assertSame($existing, $returned);
        $this->assertSame('New Title', $existing->getTitle());
        $this->assertSame('New Author', $existing->getAuthorName());
    }

    // -------------------------------------------------------------------------
    // publicationYear ↔ publishedAt round-trip
    // -------------------------------------------------------------------------

    public function testPublicationYearRoundTrip(): void
    {
        // POST publicationYear: 2001 → stored as 2001-01-01 → GET returns publicationYear: 2001.
        // Day and month components are silently discarded by BookMapper (design decision).
        $resource = $this->buildResource(publicationYear: 2001);
        $book     = $this->mapper->toEntity($resource);

        $roundTripped = $this->mapper->toResource($book);

        $this->assertSame(2001, $roundTripped->publicationYear);
    }

    // -------------------------------------------------------------------------
    // Contract checks
    // -------------------------------------------------------------------------

    public function testGetSupportedResourceClassReturnsBookResource(): void
    {
        $this->assertSame(BookResource::class, $this->mapper->getSupportedResourceClass());
    }

    public function testGetSupportedEntityClassReturnsBook(): void
    {
        $this->assertSame(Book::class, $this->mapper->getSupportedEntityClass());
    }
}
