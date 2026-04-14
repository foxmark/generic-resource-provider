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
}