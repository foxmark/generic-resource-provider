<?php

namespace App\EventListener\Doctrine;

use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use App\Event\PreChangeEventInterface;
use App\Event\PostChangeEventInterface;
use App\EventListener\Doctrine\Interface\NotifiableUpdatedInterface;
use App\EventListener\Doctrine\Trait\DoctrineEventListenerTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsDoctrineListener(event: Events::preUpdate, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 500, connection: 'default')]
class EntityUpdatedEventListener
{
    use DoctrineEventListenerTrait;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!($entity instanceof NotifiableUpdatedInterface)) {
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

        $this->eventDispatcher->dispatch($event, $eventListenerClassName::getPreUpdateEventName());
    }

    public function postUpdate(PostUpdateEventArgs $args):void
    {
        $entity = $args->getObject();
        if (!($entity instanceof NotifiableUpdatedInterface)) {
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

        $this->eventDispatcher->dispatch($event, $eventListenerClassName::getPostUpdateEventName());
    }
}