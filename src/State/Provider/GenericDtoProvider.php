<?php

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Mapper\MapManager;
use stdClass;

final class GenericDtoProvider implements ProviderInterface
{
    private $mapManager;

    public function __construct(MapManager $mapManager) {
        $this->mapManager = $mapManager;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|null
    {
        return new stdClass();
    }
}
