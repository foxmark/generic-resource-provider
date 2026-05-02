<?php

/**
 * TASK-003: BookEventSubscriber — Unit Tests
 *
 * Phase: RED — pure unit tests, no Symfony kernel, no database.
 * Logger is mocked; no DI container needed.
 *
 * @group red
 */

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\Book;
use App\Event\BookChangeEvent;
use App\EventSubscriber\BookEventSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BookEventSubscriber.
 *
 * Covered contract points:
 *  - getSubscribedEvents() registration for CREATED and UPDATED
 *  - onBookCreated() calls logger->info with correct message and context array
 *  - onBookUpdated() calls logger->info with correct message and context array
 *  - BookChangeEvent fields (getBook()) are read correctly
 */
class BookEventSubscriberTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private BookEventSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->logger     = $this->createMock(LoggerInterface::class);
        $this->subscriber = new BookEventSubscriber($this->logger);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Build a Book with a known id via reflection (entity is never persisted in unit tests).
     */
    private function buildBook(
        int $id,
        string $title,
        string $isbn,
        string $authorName = 'Test Author',
    ): Book {
        $book = new Book();
        $book->setTitle($title);
        $book->setIsbn($isbn);
        $book->setAuthorName($authorName);

        $ref  = new \ReflectionClass($book);
        $prop = $ref->getProperty('id');
        $prop->setValue($book, $id);

        return $book;
    }

    // -------------------------------------------------------------------------
    // Subscriber registration
    // -------------------------------------------------------------------------

    public function testSubscribesToBookCreatedEvent(): void
    {
        $events = BookEventSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(BookChangeEvent::CREATED, $events);
        $this->assertSame('onBookCreated', $events[BookChangeEvent::CREATED]);
    }

    public function testSubscribesToBookUpdatedEvent(): void
    {
        $events = BookEventSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(BookChangeEvent::UPDATED, $events);
        $this->assertSame('onBookUpdated', $events[BookChangeEvent::UPDATED]);
    }

    // -------------------------------------------------------------------------
    // onBookCreated
    // -------------------------------------------------------------------------

    public function testOnBookCreatedLogsInfoWithCorrectContext(): void
    {
        $book  = $this->buildBook(id: 7, title: 'Clean Code', isbn: '978-0132350884');
        $event = new BookChangeEvent($book);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Book created',
                [
                    'entity_class' => Book::class,
                    'entity_id'    => 7,
                    'isbn'         => '978-0132350884',
                    'title'        => 'Clean Code',
                ]
            );

        $this->subscriber->onBookCreated($event);
    }

    public function testOnBookCreatedUsesInfoLevel(): void
    {
        $book  = $this->buildBook(id: 1, title: 'Some Book', isbn: '978-0201485677');
        $event = new BookChangeEvent($book);

        // Confirm info() is called, not warning/error.
        $this->logger->expects($this->once())->method('info');
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('error');

        $this->subscriber->onBookCreated($event);
    }

    // -------------------------------------------------------------------------
    // onBookUpdated
    // -------------------------------------------------------------------------

    public function testOnBookUpdatedLogsInfoWithCorrectContext(): void
    {
        $book  = $this->buildBook(id: 12, title: 'Refactoring', isbn: '978-0201485677');
        $event = new BookChangeEvent($book);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Book updated',
                [
                    'entity_class' => Book::class,
                    'entity_id'    => 12,
                    'isbn'         => '978-0201485677',
                    'title'        => 'Refactoring',
                ]
            );

        $this->subscriber->onBookUpdated($event);
    }

    public function testOnBookUpdatedUsesInfoLevel(): void
    {
        $book  = $this->buildBook(id: 2, title: 'Another Book', isbn: '978-0201633610');
        $event = new BookChangeEvent($book);

        $this->logger->expects($this->once())->method('info');
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('error');

        $this->subscriber->onBookUpdated($event);
    }

    // -------------------------------------------------------------------------
    // BookChangeEvent field reads
    // -------------------------------------------------------------------------

    public function testBookChangeEventExposesBookViaGetBook(): void
    {
        $book  = $this->buildBook(id: 5, title: 'DDD', isbn: '978-0321125217');
        $event = new BookChangeEvent($book);

        $this->assertSame($book, $event->getBook());
    }

    public function testBookChangeEventCreatedConstant(): void
    {
        $this->assertSame('book.created', BookChangeEvent::CREATED);
    }

    public function testBookChangeEventUpdatedConstant(): void
    {
        $this->assertSame('book.updated', BookChangeEvent::UPDATED);
    }

    public function testBookChangeEventGetCreatedEventNameMatchesConstant(): void
    {
        $this->assertSame(BookChangeEvent::CREATED, BookChangeEvent::getCreatedEventName());
    }

    public function testBookChangeEventGetUpdatedEventNameMatchesConstant(): void
    {
        $this->assertSame(BookChangeEvent::UPDATED, BookChangeEvent::getUpdatedEventName());
    }
}
