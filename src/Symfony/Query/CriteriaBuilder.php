<?php

namespace AtpCore\Symfony\Query;

use AtpCore\Symfony\Context\ClientContext;
use AtpCore\Symfony\Resource\CompiledResource;
use AtpCore\Symfony\Resource\Condition;
use AtpCore\Symfony\Resource\MetadataPaths;
use AtpCore\Symfony\Resource\Where;
use Doctrine\ORM\QueryBuilder;

/**
 * Applies criteria to a query with the legacy filter-semantics; the scope is always applied last
 */
final class CriteriaBuilder
{
    private const OPERATORS = ['eq', 'neq', 'like', 'lt', 'lte', 'gt', 'gte', 'isnull', 'isnotnull', 'in', 'notin'];

    // group => [expression, add-method, parameter-prefix]
    private const GROUPS = [
        'AND' => ['andX', 'andWhere', 'customAnd'],
        'OR' => ['orX', 'orWhere', 'customOr'],
        'OR_AND' => ['andX', 'orWhere', 'customOrAnd'],
        'AND_OR' => ['orX', 'andWhere', 'customAndOr'],
    ];

    private int $subqueries = 0;

    public function __construct(
        private readonly MetadataPaths $paths,
    ) {}

    /**
     * @throws InvalidQueryException
     */
    public function apply(QueryBuilder $qb, JoinRegistry $joins, CompiledResource $resource, ClientContext $context, Criteria $criteria, bool $withOrder = true): void
    {
        // Set custom filter (from request)
        if (!empty($criteria->filter)) $this->applyFilter($qb, $joins, $resource, $criteria->filter);

        // Set named filters
        if (!empty($criteria->defaultFilter)) {
            $filters = array_change_key_case($resource->filters);
            foreach ($criteria->defaultFilter as $name) {
                $build = $filters[strtolower((string) $name)] ?? null;
                if ($build === null) throw new InvalidQueryException(['Provided unsupported/unknown default-filter'], 200);
                $this->applyWhere($qb, $joins, $build, $context);
            }
        }

        // Set where-conditions from code
        foreach ($criteria->extraWhere as $build) {
            $this->applyWhere($qb, $joins, $build, $context);
        }

        // Set scopes (last, so a client OR-filter can't bypass them; access is checked by Query::assertAllowed)
        if ($criteria->scoped) {
            foreach ($resource->scopes ?? [] as $build) {
                $this->applyWhere($qb, $joins, $build, $context);
            }
        }

        // Set grouping
        foreach ($criteria->groupBy ?? [] as $field) {
            $qb->addGroupBy($this->requestField($qb, $joins, $resource, (string) $field));
        }

        // Set sorting
        if ($withOrder) {
            if (!empty($criteria->orderBy)) {
                foreach ($criteria->orderBy as $order) {
                    $direction = strtoupper($order['direction'] ?? 'ASC');
                    if (!in_array($direction, ['ASC', 'DESC'], true)) throw new InvalidQueryException(['invalidQuery' => "Not allowed direction: {$direction}"]);
                    $qb->addOrderBy($this->requestField($qb, $joins, $resource, (string) ($order['field'] ?? '')), $direction);
                }
            } else {
                foreach ($resource->defaultOrder as $path => $order) {
                    $qb->addOrderBy($joins->field($path), $order->value);
                }
            }
        }

        // Set distinct (to-many joins give duplicate rows)
        if ($joins->getHasToMany()) $qb->distinct();
    }

    /**
     * Convert conditions of a where-builder to one DQL-expression (null if no conditions)
     *
     * @param \Closure(string): string $field resolves a path to a DQL-expression
     * @param ?string $entity entity of the alias (needed for exists())
     * @param string $alias DQL-alias the paths are relative to (needed for exists())
     */
    public function toExpression(QueryBuilder $qb, Where $where, \Closure $field, ?string $entity = null, string $alias = JoinRegistry::ROOT): ?string
    {
        $expressions = array_map(fn (Condition $c) => $this->conditionToExpression($qb, $c, $field, $entity, $alias), $where->conditions());
        if (empty($expressions)) return null;

        // Return
        return (string) $qb->expr()->andX(...$expressions);
    }

    private function applyWhere(QueryBuilder $qb, JoinRegistry $joins, \Closure $build, ClientContext $context): void
    {
        $where = new Where();
        $build($where, $context);
        $expression = $this->toExpression($qb, $where, fn (string $path) => $joins->field($path), $joins->getRootEntity());
        if ($expression !== null) $qb->andWhere($expression);
    }

    private function conditionToExpression(QueryBuilder $qb, Condition $condition, \Closure $field, ?string $entity, string $alias): string
    {
        // OR-group of AND-branches
        if ($condition->operator === 'or') {
            $branches = array_filter(array_map(fn (Where $branch) => $this->toExpression($qb, $branch, $field, $entity, $alias), $condition->value));
            return (string) $qb->expr()->orX(...$branches);
        }

        // EXISTS-subquery on a to-many relation (optionally behind to-one relations: 'Company.Subscriptions')
        if ($condition->operator === 'exists') {
            if ($entity === null) throw new InvalidQueryException(['invalidQuery' => 'exists() is not supported here']);
            $segments = explode('.', $condition->path);
            $relation = array_pop($segments);
            if (!empty($segments)) {
                $ownerPath = implode('.', $segments);
                $resolved = $this->paths->resolve($entity, $ownerPath);
                [$metadata, $last] = end($resolved);
                $entity = $metadata->getAssociationTargetClass($last);
                $identifier = $this->paths->metadata($entity)->getSingleIdentifierFieldName();
                $alias = strstr($field("{$ownerPath}.{$identifier}"), '.', true);
            }
            return $this->existsExpression($qb, $entity, $alias, $relation, $condition->value);
        }

        // Raw DQL
        if ($condition->operator === 'raw') {
            foreach ($condition->parameters as $name => $value) {
                $qb->setParameter($name, $value);
            }
            return "({$condition->value})";
        }

        $expression = $field($condition->path);
        if ($condition->operator === 'isNull') return $qb->expr()->isNull($expression);
        if ($condition->operator === 'isNotNull') return $qb->expr()->isNotNull($expression);

        // Bind value
        $parameter = 'w' . $qb->getParameters()->count();
        $qb->setParameter($parameter, $condition->value);

        // Return
        return (string) $qb->expr()->{$condition->operator}($expression, ':' . $parameter);
    }

    /**
     * EXISTS (SELECT 1 FROM <target> x WHERE x.<mappedBy> = <alias> AND <criteria>)
     */
    private function existsExpression(QueryBuilder $qb, string $entity, string $alias, string $relation, array $criteria): string
    {
        $metadata = $this->paths->metadata($entity);
        $target = $metadata->getAssociationTargetClass($relation);
        $subAlias = 'x' . (++$this->subqueries);

        // Conditions (paths relative to the target, joined inside the subquery)
        $joins = [];
        $conditions = ["{$subAlias}.{$metadata->getAssociationMappedByTargetField($relation)} = {$alias}"];
        foreach ($criteria as $path => $value) {
            $segments = explode('.', $path);
            $current = $subAlias;
            foreach (array_slice($segments, 0, -1) as $segment) {
                $joinAlias = "{$current}_{$segment}";
                $joins[$joinAlias] = "JOIN {$current}.{$segment} {$joinAlias}";
                $current = $joinAlias;
            }
            $parameter = 'w' . $qb->getParameters()->count();
            $qb->setParameter($parameter, $value);
            $conditions[] = "{$current}." . end($segments) . (is_array($value) ? " IN (:{$parameter})" : " = :{$parameter}");
        }

        // Return
        return sprintf('EXISTS (SELECT 1 FROM %s %s %s WHERE %s)', $target, $subAlias, implode(' ', $joins), implode(' AND ', $conditions));
    }

    private function applyFilter(QueryBuilder $qb, JoinRegistry $joins, CompiledResource $resource, array $filter): void
    {
        foreach (self::GROUPS as $group => [$combine, $add, $prefix]) {
            if (!isset($filter[$group]) || !is_array($filter[$group])) continue;

            $conditions = $qb->expr()->{$combine}();
            foreach ($filter[$group] as $k => $params) {
                $field = $this->requestField($qb, $joins, $resource, (string) ($params[0] ?? ''));
                $operator = (string) ($params[1] ?? '');
                if (!in_array(strtolower($operator), self::OPERATORS, true)) {
                    throw new InvalidQueryException(['invalidQuery' => "Not allowed operator: {$operator}"]);
                }

                // Bind value (only when given, like legacy)
                $valueKey = $prefix . $this->sanitizeField($field) . $k;
                if (isset($params[2])) $qb->setParameter($valueKey, $params[2]);

                $conditions->add($qb->expr()->{$operator}($field, ':' . $valueKey));
            }
            $qb->{$add}($conditions);
        }
    }

    /**
     * Resolve a field from the request: root-field, or Alias.field via an (automatic or custom) alias
     */
    private function requestField(QueryBuilder $qb, JoinRegistry $joins, CompiledResource $resource, string $field): string
    {
        $root = $this->paths->metadata($resource->entity);
        if (!str_contains($field, '.')) {
            if (!$root->hasField($field) && !$root->hasAssociation($field)) {
                throw new InvalidQueryException(['invalidQuery' => "Unknown field: {$field}"]);
            }
            return JoinRegistry::ROOT . ".{$field}";
        }

        [$aliasName, $property] = explode('.', $field, 2);
        $alias = $resource->aliases[$aliasName] ?? null;
        if ($alias === null) throw new InvalidQueryException(['invalidQuery' => "Unknown alias: {$aliasName}"]);
        if (str_contains($property, '.')) throw new InvalidQueryException(['invalidQuery' => "Unknown field: {$field}"]);

        // Validate property on the aliased entity
        try {
            $resolved = $this->paths->resolve($resource->entity, $alias->path);
            [$metadata, $last] = end($resolved);
            $this->paths->resolve($metadata->getAssociationTargetClass($last), $property);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidQueryException(['invalidQuery' => $e->getMessage()]);
        }

        // Join alias (with join-condition, if any)
        $with = null;
        if ($alias->on !== null) {
            $where = new Where();
            ($alias->on)($where);
            $with = $this->toExpression($qb, $where, fn (string $path) => "{$aliasName}.{$path}");
        }
        $dqlAlias = $joins->alias($aliasName, $alias->path, $with);

        // Return
        return "{$dqlAlias}.{$property}";
    }

    /**
     * Parameter-name part, equal to legacy AbstractRepository::sanitizeField
     */
    private function sanitizeField(string $field): string
    {
        return ucfirst(str_replace(['.', '(', ')', ',', ' '], '', $field));
    }
}
