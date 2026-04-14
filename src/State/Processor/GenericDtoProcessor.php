<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Mapper\MapManager;
use stdClass;

final class GenericDtoProcessor implements ProcessorInterface
{
    private $mapManager;

    public function __construct(MapManager $mapManager) {
        $this->mapManager = $mapManager;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        return new stdClass();
    }
}
