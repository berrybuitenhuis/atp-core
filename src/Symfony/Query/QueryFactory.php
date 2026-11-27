<?php

namespace AtpCore\Symfony\Query;

use AtpCore\Symfony\Context\ClientContext;
use AtpCore\Symfony\Projection\ProjectionExecutor;
use AtpCore\Symfony\Resource\MetadataPaths;
use AtpCore\Symfony\Resource\ResourceCompiler;
use AtpCore\Symfony\Resource\ResourceRegistry;
use Doctrine\ORM\EntityManagerInterface;

final class QueryFactory
{
    public function __construct(
        private readonly ResourceRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
        private readonly CriteriaBuilder $criteriaBuilder,
        private readonly ProjectionExecutor $executor,
        private readonly MetadataPaths $paths,
        private readonly ResourceCompiler $compiler,
    ) {}

    /**
     * @param class-string<\AtpCore\Symfony\Resource\Resource> $resourceClass
     */
    public function create(string $resourceClass, ClientContext $context): Query
    {
        return new Query(
            $this->entityManager,
            $this->registry->get($resourceClass, $context),
            $context,
            $this->criteriaBuilder,
            $this->executor,
            $this->paths,
        );
    }

    /**
     * Query on an entity without resource (no scope), e.g. for resolvers
     *
     * @template T of object
     * @param class-string<T> $entity
     * @param ?ClientContext $context needed when the selection contains nested computed fields with a where on the context
     * @return EntityQuery<T>
     */
    public function of(string $entity, ?ClientContext $context = null): EntityQuery
    {
        return new EntityQuery($this->entityManager, $this->paths, $this->criteriaBuilder, $this->executor, $this->compiler, $entity, $context);
    }
}
