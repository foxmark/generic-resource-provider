<?php

namespace App\State\Pagination;

use ApiPlatform\State\Pagination\PaginatorInterface;
use App\Mapper\EntityMapperInterface;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Traversable;

/**
 * Wraps a Doctrine Paginator, maps each entity to a DTO on iteration.
 *
 * Implements PaginatorInterface so API Platform can emit correct
 * hydra:totalItems, hydra:view, and Link headers automatically.
 *
 * @implements PaginatorInterface<object>
 */
final class MappedPaginator implements PaginatorInterface, \IteratorAggregate, \Countable
{
    private int $totalItems;

    public function __construct(
        private readonly DoctrinePaginator $doctrinePaginator,
        private readonly EntityMapperInterface $mapper,
        private readonly int $currentPage,
        private readonly int $itemsPerPage,
    ) {
        $this->totalItems = count($this->doctrinePaginator);
    }

    /** {@inheritdoc} */
    public function getCurrentPage(): float
    {
        return (float) $this->currentPage;
    }

    /** {@inheritdoc} */
    public function getLastPage(): float
    {
        if ($this->itemsPerPage === 0 || $this->totalItems === 0) {
            return 1.0;
        }

        return (float) ceil($this->totalItems / $this->itemsPerPage);
    }

    /** {@inheritdoc} */
    public function getItemsPerPage(): float
    {
        return (float) $this->itemsPerPage;
    }

    /** {@inheritdoc} */
    public function getTotalItems(): float
    {
        return (float) $this->totalItems;
    }

    /**
     * Iterates the Doctrine result set, mapping each entity to its DTO.
     */
    public function getIterator(): Traversable
    {
        foreach ($this->doctrinePaginator as $entity) {
            yield $this->mapper->toResource($entity);
        }
    }

    public function count(): int
    {
        return $this->totalItems;
    }
}