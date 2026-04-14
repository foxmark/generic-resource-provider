<?php

namespace App\Mapper;

use App\ApiResource\BookResource;
use App\Entity\Book;
use App\Mapper\EntityMapperInterface;
use DateTimeImmutable;

final class BookMapper implements EntityMapperInterface
{
    public function getSupportedResourceClass(): string
    {
        return BookResource::class;
    }

    public function getSupportedEntityClass(): string
    {
        return Book::class;
    }

    public function toResource(object $entity): BookResource
    {
        /** @var Book $entity */
        $resource = new BookResource();

        $resource->id             = $entity->getId();
        $resource->title          = $entity->getTitle();
        $resource->isbn           = $entity->getIsbn();
        $resource->authorName     = $entity->getAuthorName();
        $resource->available      = $entity->isAvailable();

        $resource->publicationYear = $entity->getPublishedAt()?->format('Y')
            ? (int) $entity->getPublishedAt()->format('Y')
            : null;

        return $resource;
    }

    public function toEntity(object $resource, ?object $entity = null): Book
    {
        /** @var BookResource $resource */
        $book = $entity ?? new Book();

        $book->setTitle($resource->title);
        $book->setIsbn($resource->isbn);
        $book->setAuthorName($resource->authorName);
        $book->setAvailable($resource->available);

        $book->setPublishedAt(
            $resource->publicationYear !== null
                ? new DateTimeImmutable(sprintf('%d-01-01', $resource->publicationYear))
                : null
        );

        return $book;
    }
}
