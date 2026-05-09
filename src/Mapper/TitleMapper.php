<?php

namespace App\Mapper;

use App\ApiResource\TitleResource;
use App\Entity\Title;

final class TitleMapper implements EntityMapperInterface
{
    public function getSupportedResourceClass(): string
    {
        return TitleResource::class;
    }

    public function getSupportedEntityClass(): string
    {
        return Title::class;
    }

    public function toResource(object $entity): TitleResource
    {
        /** @var Title $entity */
        $resource = new TitleResource();

        $resource->id              = $entity->getId();
        $resource->title           = $entity->getTitle();
        $resource->director        = $entity->getDirector();
        $resource->releaseYear     = $entity->getReleaseYear();
        $resource->durationMinutes = $entity->getDurationMinutes();

        return $resource;
    }

    public function toEntity(object $resource, ?object $entity = null): Title
    {
        /** @var TitleResource $resource */
        $title = $entity ?? new Title();

        $title->setTitle($resource->title);
        $title->setDirector($resource->director);
        $title->setReleaseYear($resource->releaseYear);
        $title->setDurationMinutes($resource->durationMinutes);

        return $title;
    }
}
