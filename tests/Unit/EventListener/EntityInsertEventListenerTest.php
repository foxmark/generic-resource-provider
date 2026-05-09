<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\Doctrine\EntityInsertEventListener;
use App\EventListener\Doctrine\Interface\NotifiableInsertInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityInsertEventListenerTest extends TestCase
{
    private EventDispatcherInterface&MockObject $dispatcher;
    private EntityInsertEventListener $listener;
    private ObjectManager&MockObject $om;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->listener = new EntityInsertEventListener($this->dispatcher);
        $this->om = $this->createMock(ObjectManager::class);
    }

    public function testPrePersistDoesNotDispatchWhenEventClassDoesNotExist(): void
    {
        $entity = new FakeInsertableEntity();
        $args = new PrePersistEventArgs($entity, $this->om);

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->listener->prePersist($args);
    }

    public function testPostPersistDoesNotDispatchWhenEventClassDoesNotExist(): void
    {
        $entity = new FakeInsertableEntity();
        $args = new PostPersistEventArgs($entity, $this->om);

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->listener->postPersist($args);
    }

    public function testPrePersistDoesNotDispatchWhenEntityIsNotNotifiable(): void
    {
        $entity = new \stdClass();
        $args = new PrePersistEventArgs($entity, $this->om);

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->listener->prePersist($args);
    }

    public function testPostPersistDoesNotDispatchWhenEntityIsNotNotifiable(): void
    {
        $entity = new \stdClass();
        $args = new PostPersistEventArgs($entity, $this->om);

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->listener->postPersist($args);
    }
}

/**
 * Resolves to App\Event\FakeInsertableEntityChangeEvent which does not exist,
 * so the class_exists guard in the listener returns false.
 */
class FakeInsertableEntity implements NotifiableInsertInterface {}
