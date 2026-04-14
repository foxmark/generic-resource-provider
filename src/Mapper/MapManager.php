<?php

namespace App\Mapper;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class MapManager
{
    private array $mappers;

    public function __construct(
        #[AutowireIterator(EntityMapperInterface::TAG)]
        iterable $mappers,
    ) {
        $this->mappers = array_reduce(
            iterator_to_array($mappers, false),
            fn(array $carry, EntityMapperInterface $map) => $carry + [$map->getSupportedResourceClass() => $map],
            []
        );
    }

    public function getMapper(string $resourceClassName)
    {
        if(!isset($this->mappers[$resourceClassName])) {
            throw new \RuntimeException("No mapper found for: {$resourceClassName}");
        }

        return $this->mappers[$resourceClassName];
    }
}