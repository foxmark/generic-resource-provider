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
}
