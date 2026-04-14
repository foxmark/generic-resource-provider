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
        $this->mappers = iterator_to_array($mappers);
    }

    public function getMapper(string $resourceClassName)
    {
        foreach($this->mappers as $mapper) {
            if($mapper->getSupportedResourceClass() === $resourceClassName) {
                return $mapper;
            }
        }

        throw new \RuntimeException("No mapper found for: {$resourceClassName}");
    }
}