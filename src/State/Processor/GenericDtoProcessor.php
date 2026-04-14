<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Mapper\MapManager;
use Doctrine\ORM\EntityManagerInterface;

final class GenericDtoProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly MapManager $mapManager,
        private readonly EntityManagerInterface $em,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $mapper     = $this->mapManager->getMapper($operation->getClass());
        $repository = $this->em->getRepository($mapper->getSupportedEntityClass());

        if ($operation instanceof Delete) {
            $entity = $repository->find($uriVariables['id']);
            if ($entity !== null) {
                $this->em->remove($entity);
                $this->em->flush();
            }

            return null;
        }

        $existing = ($operation instanceof Post)
            ? null
            : $repository->find($uriVariables['id']);

        $entity = $mapper->toEntity($data, $existing);

        $this->em->persist($entity);
        $this->em->flush();

        return $mapper->toResource($entity);
    }
}
