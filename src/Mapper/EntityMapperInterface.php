<?php

namespace App\Mapper;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(EntityMapperInterface::TAG)]
interface EntityMapperInterface
{
    public const TAG = 'state.processor';

    public function getSupportedResourceClass(): string;

    public function getSupportedEntityClass(): string;

    public function toResource(object $entity): object;

    /**
     * Map a DTO onto an entity. Pass an existing entity for updates,
     * omit (or pass null) for creates.
     */
    public function toEntity(object $resource, ?object $entity = null): object;
}