<?php

namespace AtpCore\Symfony\Query;

use AtpCore\Symfony\Context\ClientContext;
use AtpCore\Symfony\Projection\ProjectionExecutor;
use AtpCore\Symfony\Resource\MetadataPaths;
use AtpCore\Symfony\Resource\Output;
use AtpCore\Symfony\Resource\ResourceCompiler;
use AtpCore\Symfony\Resource\Where;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Query on an entity without resource (no scope, no exposure), e.g. for resolvers; paths like in resources, no DQL
 *
 * @template T of object
 */
final class EntityQuery
{
    /**
     * @var list<\Closure>
     */
    private array $wheres = [];

    /**
     * @var array<string, Order>
     */
    private array $orderBy = [];

    private ?int $limit = null;

    /**
     * @param class-string<T> $entity
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MetadataPaths $paths,
        private readonly CriteriaBuilder $criteriaBuilder,
        private readonly ProjectionExecutor $executor,
        private readonly ResourceCompiler $compiler,
        private readonly string $entity,
        private readonly ?ClientContext $context = null,
    ) {}

    /**
     * Conditions (combined with AND)
     *
     * @param \Closure $build fn (Where $w)
     */
    public function where(\Closure $build): static
    {
        $this->wheres[] = $build;
        return $this;
    }

    /**
     * @param array<string, Order> $orderBy
     */
    public function orderBy(array $orderBy): static
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    public function limit(?int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Records as arrays in the shape of the selection (like expose; null relations are kept)
     *
     * @return list<array>
     */
    public function rows(Output $output): array
    {
        [$qb, $joins] = $this->build();
        $node = $this->compiler->compileSelection($this->entity, $output->getSelection(), $this->context);

        // Return
        return $this->executor->fetch($qb, $node, $joins, $this->context, top: false);
    }

    /**
     * Values of one field of the entity
     *
     * @return list<mixed>
     */
    public function column(string $field): array
    {
        return array_column($this->rows(Output::of($field)), $field);
    }

    /**
     * @return array{\Doctrine\ORM\QueryBuilder, JoinRegistry}
     */
    private function build(): array
    {
        $qb = $this->entityManager->createQueryBuilder()->from($this->entity, JoinRegistry::ROOT);
        $joins = new JoinRegistry($qb, $this->paths, $this->entity);

        // Conditions
        foreach ($this->wheres as $build) {
            $where = new Where();
            $build($where);
            $expression = $this->criteriaBuilder->toExpression($qb, $where, fn (string $path) => $joins->field($path), $this->entity);
            if ($expression !== null) $qb->andWhere($expression);
        }

        // Sorting and limit
        foreach ($this->orderBy as $path => $direction) {
            $qb->addOrderBy($joins->field($path), $direction->value);
        }
        if ($this->limit !== null) $qb->setMaxResults($this->limit);
        if ($joins->getHasToMany()) $qb->distinct();

        // Return
        return [$qb, $joins];
    }
}
