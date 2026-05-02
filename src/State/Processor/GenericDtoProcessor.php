<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Mapper\MapManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class GenericDtoProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly MapManager $mapManager,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
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

        $existing = null;

        if ($operation instanceof Put || $operation instanceof Patch) {
            if (!$existing = $repository->find($uriVariables['id'])) {
                throw new ItemNotFoundException();
            }
        }

        $entity = $mapper->toEntity($data, $existing);

        $violations = $this->validator->validate($entity);
        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $this->em->persist($entity);
        $this->em->flush();

        return $mapper->toResource($entity);
    }
}
