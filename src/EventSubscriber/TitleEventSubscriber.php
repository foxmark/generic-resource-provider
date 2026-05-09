<?php

namespace App\EventSubscriber;

use App\Event\TitleChangeEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TitleEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Target('doctrineEntityLogger')]
        private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'Title.post.create' => 'onTitleCreated',
        ];
    }

    public function onTitleCreated(TitleChangeEvent $event): void
    {
        $title = $event->getTitle();
        $this->logger->info('Title created', [
            'entity_class' => $title::class,
            'entity_id'    => $title->getId(),
            'title'        => $title->getTitle(),
            'director'     => $title->getDirector(),
        ]);
    }
}
