<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\Doctrine\EntityUpdatedEventListener;
use App\EventListener\Doctrine\Interface\NotifiableUpdatedInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityUpdatedEventListenerTest extends TestCase
{
    private EventDispatcherInterface&MockObject $dispatcher;
    private EntityUpdatedEventListener $listener;
    private EntityManagerInterface&MockObject $em;
    private ObjectManager&MockObject $om;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->listener = new EntityUpdatedEventListener($this->dispatcher);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->om = $this->createMock(ObjectManager::class);
    }

    public function testPreUpdateDoesNotDispatchWhenEventClassDoesNotExist(): void
    {
        $entity = new FakeUpdatableEntity();
        $changeSet = [];
        $args = new PreUpdateEventArgs($entity, $this->em, $changeSet);

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->listener->preUpdate($args);
    }

    public function testPostUpdateDoesNotDispatchWhenEventClassDoesNotExist(): void
    {
        $entity = new FakeUpdatableEntity();
        $args = new PostUpdateEventArgs($entity, $this->om);

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->listener->postUpdate($args);
    }

    public function testPreUpdateDoesNotDispatchWhenEntityIsNotNotifiable(): void
    {
        $entity = new \stdClass();
        $changeSet = [];
        $args = new PreUpdateEventArgs($entity, $this->em, $changeSet);

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->listener->preUpdate($args);
    }

    public function testPostUpdateDoesNotDispatchWhenEntityIsNotNotifiable(): void
    {
        $entity = new \stdClass();
        $args = new PostUpdateEventArgs($entity, $this->om);

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->listener->postUpdate($args);
    }
}

/**
 * Resolves to App\Event\FakeUpdatableEntityChangeEvent which does not exist,
 * so the class_exists guard in the listener returns false.
 */
class FakeUpdatableEntity implements NotifiableUpdatedInterface {}
