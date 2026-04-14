<?php

namespace App\State\Provider;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use App\Mapper\MapManager;
use App\State\Pagination\MappedPaginator;
use App\Repository\DefaultOrderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

final class GenericDtoProvider implements ProviderInterface
{
    public function __construct(
        private readonly MapManager $mapManager,
        private readonly EntityManagerInterface $em,
        private readonly Pagination $pagination,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|null
    {
        $mapper = $this->mapManager->getMapper($operation->getClass());
        $repository = $this->em->getRepository($mapper->getSupportedEntityClass());

        if ($operation instanceof GetCollection) {
            $page         = $this->pagination->getPage($context);
            $itemsPerPage = $this->pagination->getLimit($operation, $context);
            $offset       = $this->pagination->getOffset($operation, $context);

            $qb = $repository->createQueryBuilder('e')
                ->setFirstResult($offset)
                ->setMaxResults($itemsPerPage);

            if ($repository instanceof DefaultOrderRepositoryInterface) {
                foreach ($repository->getDefaultOrder() as $field => $direction) {
                    $qb->addOrderBy("e.{$field}", $direction);
                }
            }

            return new MappedPaginator(
                new DoctrinePaginator($qb),
                $mapper,
                (int) $page,
                (int) $itemsPerPage,
            );
        }

        $entity = $repository->find($uriVariables['id']);

        return $entity !== null ? $mapper->toResource($entity) : null;
    }
}
