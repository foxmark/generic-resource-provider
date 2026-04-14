<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use stdClass;

final class GenericDtoProcessor implements ProcessorInterface
{
    public function __construct() {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        return new stdClass();
    }
}
