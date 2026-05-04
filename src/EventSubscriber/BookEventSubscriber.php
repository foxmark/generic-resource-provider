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
            'Book.post.create' => 'onBookCreated',
            'Book.post.update' => 'onBookUpdated',
            'Book.pre.create' => 'beforeBookCreate',
            'Book.pre.update' => 'beforeBookUpdate',
        ];
    }

    public function onBookCreated(BookChangeEvent $event): void
    {
        $book = $event->getBook();
        $this->logger->info('Book created', [
            'entity_class' => $book::class,
            'entity_id'    => $book->getId(),
            'isbn'         => $book->getIsbn(),
            'title'        => $book->getTitle(),
        ]);
    }

    public function onBookUpdated(BookChangeEvent $event): void
    {
        $book = $event->getBook();
        $this->logger->info('Book updated', [
            'entity_class' => $book::class,
            'entity_id'    => $book->getId(),
            'isbn'         => $book->getIsbn(),
            'title'        => $book->getTitle(),
        ]);
    }

    public function beforeBookCreate(BookChangeEvent $event): void
    {
        $book = $event->getBook();
        $this->logger->info('before book created', [
            'entity_class' => $book::class,
            'entity_id'    => $book->getId(),
            'isbn'         => $book->getIsbn(),
            'title'        => $book->getTitle(),
        ]);
    }

    public function beforeBookUpdate(BookChangeEvent $event): void
    {
        $book = $event->getBook();
        $this->logger->info('before book updates', [
            'entity_class' => $book::class,
            'entity_id'    => $book->getId(),
            'isbn'         => $book->getIsbn(),
            'title'        => $book->getTitle(),
        ]);
    }
}
