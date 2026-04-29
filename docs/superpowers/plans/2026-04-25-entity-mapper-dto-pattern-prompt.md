# Entity > Mapper > DTO Pattern — LLM Replication Prompt

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a new API resource called `[EntityName]` (replace every occurrence of `Book`/`book` with your entity name) following the Entity > Mapper > DTO pattern used throughout this codebase.

**Architecture:** The API layer (DTO/ApiResource) is decoupled from the persistence layer (Doctrine Entity) by a Mapper. The generic state provider and processor handle all HTTP operations automatically — you only register a new Mapper and the infrastructure does the rest.

**Tech Stack:** Symfony 7.4, API Platform 4.x, Doctrine ORM, PHP 8.2+

---

## Pattern Overview

Each domain resource requires exactly **six files**. The infrastructure classes below already exist — do not recreate them:

| Existing infrastructure | Role |
|---|---|
| `src/State/Provider/GenericDtoProvider.php` | Handles GET + GetCollection for all resources |
| `src/State/Processor/GenericDtoProcessor.php` | Handles POST/PUT/PATCH/DELETE for all resources |
| `src/Mapper/MapManager.php` | Auto-discovers mappers tagged with `EntityMapperInterface::TAG` |
| `src/State/Pagination/MappedPaginator.php` | Wraps Doctrine paginator, yields DTOs |
| `src/Mapper/EntityMapperInterface.php` | Contract all mappers must implement |
| `src/Repository/DefaultOrderRepositoryInterface.php` | Default ORDER BY contract for repositories |
| `src/EventListener/Doctrine/EntityInsertEventListener.php` | Fires `{Entity}ChangeEvent::CREATED` after `postPersist` |
| `src/EventListener/Doctrine/EntityUpdatedEventListener.php` | Fires `{Entity}ChangeEvent::UPDATED` after `postUpdate` |
| `src/EventListener/Doctrine/Interface/NotifiableInsertInterface.php` | Marker: entity wants insert events |
| `src/EventListener/Doctrine/Interface/NotifiableUpdatedInterface.php` | Marker: entity wants update events |
| `src/Event/ChangeEventInterface.php` | Contract for `getCreatedEventName()` + `getUpdatedEventName()` |

**Critical naming rule:** The `DoctrineEventListenerTrait` auto-derives the event class name as `App\Event\{EntityName}ChangeEvent`. The event class MUST follow this exact convention or events will silently not fire.

---

## File Structure

| File | Action |
|---|---|
| `src/Entity/Book.php` | Create |
| `src/ApiResource/BookResource.php` | Create |
| `src/Mapper/BookMapper.php` | Create |
| `src/Repository/BookRepository.php` | Create |
| `src/Event/BookChangeEvent.php` | Create |
| `src/EventSubscriber/BookEventSubscriber.php` | Create |

---

## Task 1: Doctrine Entity

**Files:**
- Create: `src/Entity/Book.php`

- [ ] **Step 1: Write the entity class**

```php
<?php

namespace App\Entity;

use App\Repository\BookRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use App\EventListener\Doctrine\Interface\NotifiableInsertInterface;
use App\EventListener\Doctrine\Interface\NotifiableUpdatedInterface;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'book')]
#[ORM\HasLifecycleCallbacks]
class Book implements NotifiableInsertInterface, NotifiableUpdatedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private string $isbn = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $authorName = '';

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $available = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function initTimestamps(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }

    public function getIsbn(): string { return $this->isbn; }
    public function setIsbn(string $isbn): void { $this->isbn = $isbn; }

    public function getAuthorName(): string { return $this->authorName; }
    public function setAuthorName(string $authorName): void { $this->authorName = $authorName; }

    public function getPublishedAt(): ?DateTimeImmutable { return $this->publishedAt; }
    public function setPublishedAt(?DateTimeImmutable $publishedAt): void { $this->publishedAt = $publishedAt; }

    public function isAvailable(): bool { return $this->available; }
    public function setAvailable(bool $available): void { $this->available = $available; }

    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}
```

**Rules:**
- Implement `NotifiableInsertInterface` and `NotifiableUpdatedInterface` so the Doctrine listeners fire events on persist/update.
- Add `#[ORM\PrePersist]` on `initTimestamps()` to set `createdAt` automatically.
- Use `#[ORM\HasLifecycleCallbacks]` on the class.
- Properties that differ from the DTO (e.g., `publishedAt` as `DateTimeImmutable` vs `publicationYear` as `int`) are resolved in the Mapper, not here.

- [ ] **Step 2: Generate and run the migration**

```bash
docker compose exec php symfony console make:migration
docker compose exec php symfony console doctrine:migrations:migrate
```

Expected: migration file created, schema updated with `book` table.

- [ ] **Step 3: Commit**

```bash
git add src/Entity/Book.php migrations/
git commit -m "feat: add Book entity"
```

---

## Task 2: API Resource (DTO)

**Files:**
- Create: `src/ApiResource/BookResource.php`

- [ ] **Step 1: Write the DTO class**

```php
<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\State\Processor\GenericDtoProcessor;
use App\State\Provider\GenericDtoProvider;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Book',
    operations: [
        new GetCollection(provider: GenericDtoProvider::class),
        new Get(provider: GenericDtoProvider::class),
        new Post(processor: GenericDtoProcessor::class),
        new Put(provider: GenericDtoProvider::class, processor: GenericDtoProcessor::class),
        new Patch(provider: GenericDtoProvider::class, processor: GenericDtoProcessor::class),
        new Delete(provider: GenericDtoProvider::class, processor: GenericDtoProcessor::class),
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 10,
    paginationClientItemsPerPage: true,
    paginationMaximumItemsPerPage: 30,
)]
class BookResource
{
    public ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $title = null;

    #[Assert\NotBlank]
    #[Assert\Isbn]
    public ?string $isbn = null;

    #[Assert\NotBlank]
    public ?string $authorName = null;

    #[Assert\Range(min: 1000, max: 2100)]
    public ?int $publicationYear = null;

    public bool $available = true;
}
```

**Rules:**
- Always wire `GenericDtoProvider` to GET/GetCollection and `GenericDtoProcessor` to write operations.
- Operations that need to load an existing record (PUT, PATCH, DELETE) must also specify `provider:` so the existing entity can be fetched before processing.
- Validation constraints go on the DTO, not on the entity.
- `id` is always `public ?int $id = null;` — it is set by the mapper on read and ignored on write.
- The DTO may expose different field shapes than the entity (e.g., `publicationYear: int` instead of `publishedAt: DateTimeImmutable`). The Mapper handles the translation.

- [ ] **Step 2: Verify the route is registered**

```bash
docker compose exec php symfony console debug:router | grep book
```

Expected: lines for `api_books_get_collection`, `api_books_post`, `api_books_get`, `api_books_put`, `api_books_patch`, `api_books_delete`.

- [ ] **Step 3: Commit**

```bash
git add src/ApiResource/BookResource.php
git commit -m "feat: add BookResource DTO"
```

---

## Task 3: Mapper

**Files:**
- Create: `src/Mapper/BookMapper.php`

- [ ] **Step 1: Write the mapper class**

```php
<?php

namespace App\Mapper;

use App\ApiResource\BookResource;
use App\Entity\Book;
use DateTimeImmutable;

final class BookMapper implements EntityMapperInterface
{
    public function getSupportedResourceClass(): string
    {
        return BookResource::class;
    }

    public function getSupportedEntityClass(): string
    {
        return Book::class;
    }

    public function toResource(object $entity): BookResource
    {
        /** @var Book $entity */
        $resource = new BookResource();

        $resource->id             = $entity->getId();
        $resource->title          = $entity->getTitle();
        $resource->isbn           = $entity->getIsbn();
        $resource->authorName     = $entity->getAuthorName();
        $resource->available      = $entity->isAvailable();

        $resource->publicationYear = $entity->getPublishedAt()?->format('Y')
            ? (int) $entity->getPublishedAt()->format('Y')
            : null;

        return $resource;
    }

    public function toEntity(object $resource, ?object $entity = null): Book
    {
        /** @var BookResource $resource */
        $book = $entity ?? new Book();

        $book->setTitle($resource->title);
        $book->setIsbn($resource->isbn);
        $book->setAuthorName($resource->authorName);
        $book->setAvailable($resource->available);

        $book->setPublishedAt(
            $resource->publicationYear !== null
                ? new DateTimeImmutable(sprintf('%d-01-01', $resource->publicationYear))
                : null
        );

        return $book;
    }
}
```

**Rules:**
- The class MUST implement `EntityMapperInterface`. The `#[AutoconfigureTag]` on that interface automatically registers the mapper in `MapManager` — no manual service config needed.
- `toEntity()` receives `?object $entity`: non-null on PUT/PATCH (existing entity), null on POST (new entity). Always do `$entity ?? new Book()`.
- Never set `id` in `toEntity()` — Doctrine manages it.
- Never set `createdAt` in `toEntity()` — the `#[ORM\PrePersist]` lifecycle callback handles it.
- Field shape differences (e.g., `publicationYear` ↔ `publishedAt`) are handled here, keeping entity and DTO clean.

- [ ] **Step 2: Verify mapper is auto-wired**

```bash
docker compose exec php symfony console debug:autowiring | grep BookMapper
```

Expected: `App\Mapper\BookMapper` listed as a tagged service.

- [ ] **Step 3: Commit**

```bash
git add src/Mapper/BookMapper.php
git commit -m "feat: add BookMapper"
```

---

## Task 4: Repository

**Files:**
- Create: `src/Repository/BookRepository.php`

- [ ] **Step 1: Write the repository class**

```php
<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository implements DefaultOrderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function getDefaultOrder(): array
    {
        return ['id' => 'ASC'];
    }
}
```

**Rules:**
- Always implement `DefaultOrderRepositoryInterface` and return at minimum `['id' => 'ASC']`. `GenericDtoProvider` calls `getDefaultOrder()` when building collection queries.
- Add domain-specific query methods here (e.g., `findAvailable()`). Keep them out of the entity.

- [ ] **Step 2: Commit**

```bash
git add src/Repository/BookRepository.php
git commit -m "feat: add BookRepository"
```

---

## Task 5: Change Event

**Files:**
- Create: `src/Event/BookChangeEvent.php`

- [ ] **Step 1: Write the event class**

```php
<?php

namespace App\Event;

use App\Entity\Book;
use Symfony\Contracts\EventDispatcher\Event;

class BookChangeEvent extends Event implements ChangeEventInterface
{
    public const CREATED = 'book.created';
    public const UPDATED = 'book.updated';

    public function __construct(private Book $book) {}

    public static function getCreatedEventName(): string
    {
        return self::CREATED;
    }

    public static function getUpdatedEventName(): string
    {
        return self::UPDATED;
    }

    public function getBook(): Book
    {
        return $this->book;
    }
}
```

**Rules:**
- Class name MUST be `{EntityName}ChangeEvent` in namespace `App\Event\`. The `DoctrineEventListenerTrait::getEventClassName()` derives `App\Event\BookChangeEvent` from the entity class name at runtime — any deviation silently disables events.
- Implement `ChangeEventInterface` (requires `getCreatedEventName()` and `getUpdatedEventName()`).
- Event name constants follow the pattern `'{entity_lowercase}.created'` and `'{entity_lowercase}.updated'`.

- [ ] **Step 2: Commit**

```bash
git add src/Event/BookChangeEvent.php
git commit -m "feat: add BookChangeEvent"
```

---

## Task 6: Event Subscriber

**Files:**
- Create: `src/EventSubscriber/BookEventSubscriber.php`

- [ ] **Step 1: Write the subscriber class**

```php
<?php

namespace App\EventSubscriber;

use App\Event\BookChangeEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BookEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Target('doctrineEntityLogger')]
        private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BookChangeEvent::CREATED => 'onBookCreated',
            BookChangeEvent::UPDATED => 'onBookUpdated',
        ];
    }

    public function onBookCreated(BookChangeEvent $event): void
    {
        $book = $event->getBook();
        $this->logger->info('Book created', [
            'entity_class' => $book::class,
            'entity_id'    => $book->getId(),
        ]);
    }

    public function onBookUpdated(BookChangeEvent $event): void
    {
        $book = $event->getBook();
        $this->logger->info('Book updated', [
            'entity_class' => $book::class,
            'entity_id'    => $book->getId(),
        ]);
    }
}
```

**Rules:**
- Subscribe to `BookChangeEvent::CREATED` and `BookChangeEvent::UPDATED`.
- The `#[Target('doctrineEntityLogger')]` attribute selects the named logger channel configured in `monolog.yaml`. Use this on the injected `LoggerInterface`.
- Add domain-relevant fields to the log context (e.g., `isbn`, `title`).
- This is the right place to add side-effects on create/update: emails, webhooks, cache invalidation, etc.

- [ ] **Step 2: Smoke-test the full flow**

Create a book via the API and verify the event fires:

```bash
curl -s -X POST http://localhost/api/books \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Book","isbn":"978-3-16-148410-0","authorName":"Jane Doe","publicationYear":2024}' \
  | jq .
```

Expected: `201 Created` with `id` in the response body.

Check the log:

```bash
docker compose exec php tail -20 var/log/dev.log | grep "Book created"
```

Expected: log line with `entity_id` set to the new book's ID.

- [ ] **Step 3: Commit**

```bash
git add src/EventSubscriber/BookEventSubscriber.php
git commit -m "feat: add BookEventSubscriber"
```

---

## Substitution Guide

To implement a new entity (e.g., `Author`), replace every occurrence:

| Replace | With |
|---|---|
| `Book` | `Author` |
| `book` (lowercase) | `author` |
| `BookResource` | `AuthorResource` |
| `BookMapper` | `AuthorMapper` |
| `BookRepository` | `AuthorRepository` |
| `BookChangeEvent` | `AuthorChangeEvent` |
| `BookEventSubscriber` | `AuthorEventSubscriber` |
| `book.created` | `author.created` |
| `book.updated` | `author.updated` |
| `'book'` (table name) | `'author'` |

Then update the field definitions in each file to match the new entity's data model. The infrastructure (`GenericDtoProvider`, `GenericDtoProcessor`, `MapManager`) requires no changes.
