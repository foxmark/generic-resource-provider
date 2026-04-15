<?php

namespace App\EventListener\Doctrine;

use App\Event\ChangeEventInterface;
use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use App\EventListener\Doctrine\Interface\NotifiableInsertInterface;
use App\EventListener\Doctrine\Trait\DoctrineEventListenerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

// #[AsDoctrineListener(event: Events::prePersist, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
class EntityInsertEventListener
{
    use DoctrineEventListenerTrait;

    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;

    public function __construct(EventDispatcherInterface $eventDispatcher, LoggerInterface $logger)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        // Disabled for now
        return;
        $entity = $args->getObject();
        if (!($entity instanceof NotifiableInsertInterface)) {
            return;
        }
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!($entity instanceof NotifiableInsertInterface)) {
            return;
        }

        $eventListenerClassName = self::getEventClassName($entity);
        if(!class_exists($eventListenerClassName)) {
            return;
        }

        $event = new $eventListenerClassName($entity);
        if (!($event instanceof ChangeEventInterface)) {
            return;
        }

        $this->eventDispatcher->dispatch($event, $eventListenerClassName::getCreatedEventName());
    }
}