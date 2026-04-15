<?php

namespace App\EventSubscriber;

use App\Event\BookChangeEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BookEventSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BookChangeEvent::CREATED => 'onBookCreated',
            BookChangeEvent::UPDATED => 'onBookUpdated'
        ];
    }

    public function onBookCreated(BookChangeEvent $event) 
    {
        $book = $event->getBook();
        $this->logger->info('Book created callback for ' . $book->getIsbn());
    }

    public function onBookUpdated(BookChangeEvent $event) 
    {
        $book = $event->getBook();
        $this->logger->info('Book updated callback for ' . $book->getIsbn());
    }
}
