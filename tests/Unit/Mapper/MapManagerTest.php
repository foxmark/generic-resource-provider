<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mapper;

use App\Mapper\MapManager;
use PHPUnit\Framework\TestCase;

class MapManagerTest extends TestCase
{
    public function testGetMapperThrowsRuntimeExceptionForUnmappedClass(): void
    {
        $manager = new MapManager(new \ArrayIterator([]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No mapper found for: App\SomeUnknownResource');

        $manager->getMapper('App\SomeUnknownResource');
    }
}
