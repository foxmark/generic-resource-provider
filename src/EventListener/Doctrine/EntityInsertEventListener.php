<?php

namespace App\EventListener\Doctrine;

use App\Event\PreChangeEventInterface;
use App\Event\PostChangeEventInterface;
use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use App\EventListener\Doctrine\Interface\NotifiableInsertInterface;
use App\EventListener\Doctrine\Trait\DoctrineEventListenerTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsDoctrineListener(event: Events::prePersist, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
class EntityInsertEventListener
{
    use DoctrineEventListenerTrait;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function prePersist(PrePersistEventArgs $args): void
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
        if (!($event instanceof PreChangeEventInterface)) {
            return;
        }

        $this->eventDispatcher->dispatch($event, $eventListenerClassName::getPreCreateEventName());
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
        if (!($event instanceof PostChangeEventInterface)) {
            return;
        }

        $this->eventDispatcher->dispatch($event, $eventListenerClassName::getPostCreateEventName());
    }
}