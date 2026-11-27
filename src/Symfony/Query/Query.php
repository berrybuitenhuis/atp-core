<?php

namespace AtpCore\Symfony\Query;

use AtpCore\Error;
use AtpCore\Symfony\Context\ClientContext;
use AtpCore\Symfony\Projection\FieldSelection;
use AtpCore\Symfony\Projection\ProjectionExecutor;
use AtpCore\Symfony\Request\RequestParams;
use AtpCore\Symfony\Resource\CompiledResource;
use AtpCore\Symfony\Resource\MetadataPaths;
use AtpCore\Symfony\Resource\Where;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Criteria (scope + request-filters) with two kinds of results: arrays (getList/get) or Doctrine-entities (getEntities/getEntity)
 */
final class Query
{
    private const DEFAULT_LIMIT = 25;
    private const MAX_LIMIT = 1000;

    private ?array $filter = null;
    private ?array $defaultFilter = null;
    private ?array $orderBy = null;
    private ?array $groupBy = null;
    private array $extraWhere = [];
    private bool $scoped = true;
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompiledResource $resource,
        private readonly ClientContext $context,
        private readonly CriteriaBuilder $criteriaBuilder,
        private readonly ProjectionExecutor $executor,
        private readonly MetadataPaths $paths,
    ) {}

    public function applyRequest(RequestParams $params): static
    {
        $this->filter = $params->filter;
        $this->defaultFilter = $params->defaultFilter;
        $this->orderBy = $params->orderBy;
        $this->groupBy = $params->groupBy;
        return $this->limit($params->limit, $params->offset);
    }

    /**
     * @param \Closure $build fn (Where $w, <ClientContext of the application, e.g. Session> $c)
     */
    public function where(\Closure $build): static
    {
        $this->extraWhere[] = $build;
        return $this;
    }

    public function named(string ...$names): static
    {
        $this->defaultFilter = [...($this->defaultFilter ?? []), ...$names];
        return $this;
    }

    /**
     * Skip the client-scope (only from code, never from request-parameters)
     */
    public function unscoped(): static
    {
        $this->scoped = false;
        return $this;
    }

    public function byId(int $id): static
    {
        return $this->where(fn (Where $w) => $w->eq('id', $id));
    }

    public function limit(?int $limit, ?int $offset): static
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * Get records as arrays (only the requested columns) plus paginator
     */
    public function getList(array|false|null $fields, bool $paginator = true): ProjectionResult|Error
    {
        try {
            $this->assertAllowed();
            $limit = ($this->limit > 0) ? min($this->limit, self::MAX_LIMIT) : self::DEFAULT_LIMIT;
            $offset = $this->offset ?? 0;

            // Fetch rows (none when fields === false, only the paginator)
            $rows = ($fields === false) ? null : $this->fetchRows($fields, $limit, $offset);

            // Return
            return new ProjectionResult($rows, $paginator ? $this->paginator($limit, $offset, $rows) : null);
        } catch (InvalidQueryException $e) {
            return new Error(messages: $e->getMessages(), statusCode: $e->getStatus());
        } catch (ClientNotAllowedException $e) {
            return new Error(messages: ['clientNotAllowed' => $e->getMessage()], statusCode: 403);
        }
    }

    /**
     * Get one record as array (use with byId)
     */
    public function get(array|false|null $fields): array|Error
    {
        try {
            $this->assertAllowed();

            // Fetch record (fields === false means the full exposure here)
            $rows = $this->fetchRows($fields ?: null, 1, 0);
            if (empty($rows)) return $this->notFound();

            // Return
            return $rows[0];
        } catch (InvalidQueryException $e) {
            return new Error(messages: $e->getMessages(), statusCode: $e->getStatus());
        } catch (ClientNotAllowedException $e) {
            return new Error(messages: ['clientNotAllowed' => $e->getMessage()], statusCode: 403);
        }
    }

    /**
     * Fetch the requested fields (intersected with the exposure of the client) as arrays
     *
     * @return list<array>
     */
    private function fetchRows(?array $fields, int $limit, int $offset): array
    {
        [$qb, $joins] = $this->build();
        $qb->setMaxResults($limit)->setFirstResult($offset);
        return $this->executor->fetch($qb, FieldSelection::resolve($this->resource->exposure, $fields), $joins, $this->context);
    }

    /**
     * @return list<object>
     */
    public function getEntities(): array
    {
        $this->assertAllowed();
        [$qb] = $this->build();
        $qb->select(JoinRegistry::ROOT);
        if ($this->limit !== null) $qb->setMaxResults($this->limit)->setFirstResult($this->offset ?? 0);
        return $qb->getQuery()->getResult();
    }

    /**
     * @return object|Error the entity, or notFound when outside scope
     */
    public function getEntity(): object
    {
        $this->assertAllowed();
        [$qb] = $this->build(withOrder: false);
        $entity = $qb->select(JoinRegistry::ROOT)->setMaxResults(1)->getQuery()->getOneOrNullResult();
        return $entity ?? $this->notFound();
    }

    public function exists(): bool
    {
        $this->assertAllowed();
        [$qb] = $this->build(withOrder: false);
        $qb->select(JoinRegistry::ROOT . '.id')->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult() !== null;
    }

    /**
     * Closed by default: a client without scope-declaration is not allowed (also for create)
     *
     * @throws ClientNotAllowedException
     */
    public function assertAllowed(): void
    {
        if ($this->scoped && !$this->resource->getAllowed()) {
            throw new ClientNotAllowedException($this->context->getClientIdentifier(), $this->resource->entity);
        }
    }

    /**
     * @return array{QueryBuilder, JoinRegistry}
     */
    private function build(bool $withOrder = true): array
    {
        $qb = $this->entityManager->createQueryBuilder()->from($this->resource->entity, JoinRegistry::ROOT);
        $joins = new JoinRegistry($qb, $this->paths, $this->resource->entity);
        $criteria = new Criteria($this->filter, $this->defaultFilter, $this->orderBy, $this->groupBy, $this->extraWhere, $this->scoped);
        $this->criteriaBuilder->apply($qb, $joins, $this->resource, $this->context, $criteria, $withOrder);

        // Return
        return [$qb, $joins];
    }

    /**
     * Paginator with the legacy math
     */
    private function paginator(int $limit, int $offset, ?array $rows): array
    {
        [$qb] = $this->build(withOrder: false);
        $qb->select('COUNT(DISTINCT ' . JoinRegistry::ROOT . '.id)');
        $qb->resetDQLPart('groupBy');
        $records = (int) $qb->getQuery()->getSingleScalarResult();
        $currentPage = (int) ceil($offset / $limit) + 1;

        // Legacy-correction for grouped results
        $resultCount = count($rows ?? []);
        if (!empty($this->groupBy) && $resultCount < $limit && $resultCount < $records && $currentPage == 1) {
            $records = $resultCount;
        }

        // Return
        return [
            'records' => $records,
            'pages' => (int) ceil($records / $limit),
            'currentPage' => $currentPage,
            'recordsPage' => $limit,
        ];
    }

    private function notFound(): Error
    {
        return new Error(messages: ['notFound' => $this->resource->entity . ' not found']);
    }
}
