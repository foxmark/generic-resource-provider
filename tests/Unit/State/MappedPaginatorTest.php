<?php

declare(strict_types=1);

namespace App\Tests\Unit\State;

use App\Mapper\EntityMapperInterface;
use App\State\Pagination\MappedPaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use PHPUnit\Framework\TestCase;

class MappedPaginatorTest extends TestCase
{
    private function makeDoctrinePaginatorMock(int $totalItems, array $entities = []): DoctrinePaginator
    {
        $mock = $this->createMock(DoctrinePaginator::class);
        $mock->method('count')->willReturn($totalItems);
        $mock->method('getIterator')->willReturn(new \ArrayIterator($entities));
        return $mock;
    }

    private function makeMapperMock(?object $returnValue = null): EntityMapperInterface
    {
        $mock = $this->createMock(EntityMapperInterface::class);
        if ($returnValue !== null) {
            $mock->method('toResource')->willReturn($returnValue);
        }
        return $mock;
    }

    public function testGetCurrentPageReturnsFloat(): void
    {
        $paginator = new MappedPaginator(
            $this->makeDoctrinePaginatorMock(10),
            $this->makeMapperMock(),
            currentPage: 3,
            itemsPerPage: 10,
        );

        $this->assertSame(3.0, $paginator->getCurrentPage());
    }

    public function testGetItemsPerPageReturnsFloat(): void
    {
        $paginator = new MappedPaginator(
            $this->makeDoctrinePaginatorMock(10),
            $this->makeMapperMock(),
            currentPage: 1,
            itemsPerPage: 25,
        );

        $this->assertSame(25.0, $paginator->getItemsPerPage());
    }

    public function testGetLastPageNormalCase(): void
    {
        $paginator = new MappedPaginator(
            $this->makeDoctrinePaginatorMock(25),
            $this->makeMapperMock(),
            currentPage: 1,
            itemsPerPage: 10,
        );

        $this->assertSame(3.0, $paginator->getLastPage());
    }

    public function testGetLastPageReturnsOneWhenTotalItemsIsZero(): void
    {
        $paginator = new MappedPaginator(
            $this->makeDoctrinePaginatorMock(0),
            $this->makeMapperMock(),
            currentPage: 1,
            itemsPerPage: 10,
        );

        $this->assertSame(1.0, $paginator->getLastPage());
    }

    public function testGetLastPageReturnsOneWhenItemsPerPageIsZero(): void
    {
        $paginator = new MappedPaginator(
            $this->makeDoctrinePaginatorMock(10),
            $this->makeMapperMock(),
            currentPage: 1,
            itemsPerPage: 0,
        );

        $this->assertSame(1.0, $paginator->getLastPage());
    }

    public function testGetTotalItemsReturnsFloat(): void
    {
        $paginator = new MappedPaginator(
            $this->makeDoctrinePaginatorMock(42),
            $this->makeMapperMock(),
            currentPage: 1,
            itemsPerPage: 10,
        );

        $this->assertSame(42.0, $paginator->getTotalItems());
    }

    public function testCountReturnsInt(): void
    {
        $paginator = new MappedPaginator(
            $this->makeDoctrinePaginatorMock(7),
            $this->makeMapperMock(),
            currentPage: 1,
            itemsPerPage: 10,
        );

        $this->assertSame(7, $paginator->count());
    }

    public function testGetIteratorYieldsMappedDtos(): void
    {
        $fakeEntity = new \stdClass();
        $fakeDto = new \stdClass();
        $fakeDto->name = 'mapped';

        $mapper = $this->createMock(EntityMapperInterface::class);
        $mapper->expects($this->once())
            ->method('toResource')
            ->with($this->identicalTo($fakeEntity))
            ->willReturn($fakeDto);

        $paginator = new MappedPaginator(
            $this->makeDoctrinePaginatorMock(1, [$fakeEntity]),
            $mapper,
            currentPage: 1,
            itemsPerPage: 10,
        );

        $results = iterator_to_array($paginator->getIterator());

        $this->assertCount(1, $results);
        $this->assertSame($fakeDto, $results[0]);
    }
}
