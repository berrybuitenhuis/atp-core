<?php

namespace AtpCore\Symfony\Projection;

use AtpCore\Symfony\Context\ClientContext;
use AtpCore\Symfony\Query\CriteriaBuilder;
use AtpCore\Symfony\Query\JoinRegistry;
use AtpCore\Symfony\Resource\CompiledComputed;
use AtpCore\Symfony\Resource\ExposureNode;
use AtpCore\Symfony\Resource\MetadataPaths;
use AtpCore\Symfony\Resource\Where;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Selects only the requested columns (scalar DQL, no entity-hydration); to-many relations and nested
 * computed fields are loaded with one batch-query per relation per level, resolvers once per level
 */
final class ProjectionExecutor
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MetadataPaths $paths,
        private readonly CriteriaBuilder $criteriaBuilder,
        private readonly RowAssembler $assembler,
    ) {}

    /**
     * @param bool $top true: top-level output-rules (metadata-order, empty relation disappears); false: nested rules (selection-order, null kept)
     * @return list<array>
     */
    public function fetch(QueryBuilder $qb, ExposureNode $selection, JoinRegistry $joins, ?ClientContext $context, bool $top = true): array
    {
        // Select columns
        $columns = ['c_id' => '#'];
        $types = [];
        $qb->select(JoinRegistry::ROOT . '.id AS c_id');
        $this->addColumns($qb, $joins, $selection, [], $columns, $types);

        // Fetch, enrich and convert
        $rows = $this->convert($qb->getQuery()->getScalarResult(), $types);
        $records = array_map(fn (array $row) => $this->assembler->build($row, $columns, $selection), $rows);
        $this->enrich($records, $selection, $context);

        // Return
        return array_map(fn (array $record) => $this->assembler->finalize($record, $selection, $top), $records);
    }

    /**
     * @param list<string> $prefix relation-path to this node
     * @param array<string, string> $columns result-alias => column-key
     * @param array<string, Type> $types result-alias => Doctrine-type (scalar results are not converted by Doctrine)
     */
    private function addColumns(QueryBuilder $qb, JoinRegistry $joins, ExposureNode $node, array $prefix, array &$columns, array &$types): void
    {
        // Identifier of a joined relation (null = relation is empty)
        if (!empty($prefix)) {
            $identifier = $this->paths->metadata($node->entity)->getSingleIdentifierFieldName();
            $this->addColumn($qb, $joins, implode('.', [...$prefix, $identifier]), RowAssembler::key($prefix, '#'), $columns, $types);
        }

        // Fields and dependencies of map-fields
        foreach ($node->fields as $field) {
            $this->addColumn($qb, $joins, implode('.', [...$prefix, $field]), RowAssembler::key($prefix, $field), $columns, $types);
        }
        foreach ($node->computed as $computed) {
            foreach ($computed->dependencies as $dependency) {
                $this->addColumn($qb, $joins, implode('.', [...$prefix, $dependency]), RowAssembler::key($prefix, '~' . $dependency), $columns, $types);
            }
        }

        // To-one relations (collections, nested and batch are loaded afterwards)
        foreach ($node->relations as $name => $child) {
            $this->addColumns($qb, $joins, $child, [...$prefix, $name], $columns, $types);
        }
    }

    /**
     * @param array<string, string> $columns result-alias => column-key
     * @param array<string, Type> $types result-alias => Doctrine-type
     */
    private function addColumn(QueryBuilder $qb, JoinRegistry $joins, string $path, string $key, array &$columns, array &$types): void
    {
        if (in_array($key, $columns, true)) return;
        $alias = 'c' . count($columns);
        $qb->addSelect($joins->field($path) . " AS {$alias}");
        $columns[$alias] = $key;

        // Type of the field (for conversion to PHP-values)
        $resolved = $this->paths->resolve($joins->getRootEntity(), $path);
        [$metadata, $field] = end($resolved);
        $type = $metadata->getTypeOfField($field);
        if ($type !== null) $types[$alias] = Type::getType($type);
    }

    /**
     * Convert database-values to PHP-values (DateTime, bool, ...) like entity-hydration does
     *
     * @param list<array> $rows
     * @param array<string, Type> $types
     * @return list<array>
     */
    private function convert(array $rows, array $types): array
    {
        $platform = $this->entityManager->getConnection()->getDatabasePlatform();
        foreach ($rows as &$row) {
            foreach ($types as $alias => $type) {
                if (isset($row[$alias])) $row[$alias] = $type->convertToPHPValue($row[$alias], $platform);
            }
        }
        unset($row);

        // Return
        return $rows;
    }

    /**
     * Load collections and computed fields for all records of one level (and their to-one relations)
     *
     * @param list<?array> $records internal records (by reference)
     */
    private function enrich(array &$records, ExposureNode $node, ?ClientContext $context): void
    {
        $refs = [];
        foreach ($records as &$record) {
            if ($record !== null) $refs[] = &$record;
        }
        unset($record);
        if (empty($refs)) return;

        // To-one relations
        foreach ($node->relations as $name => $child) {
            $children = [];
            foreach ($refs as &$record) {
                if ($record[$name] !== null) $children[] = &$record[$name];
            }
            unset($record);
            $this->enrich($children, $child, $context);
        }
        $ids = array_values(array_unique(array_map(fn (array $record) => $record['#id'], $refs)));

        // To-many relations
        foreach ($node->collections as $name => $child) {
            $grouped = $this->fetchChildren($node->entity, $name, $child, $ids, null, [], $context);
            foreach ($refs as &$record) {
                $record[$name] = $grouped[$record['#id']] ?? [];
            }
            unset($record);
        }

        // Computed fields
        foreach ($node->computed as $name => $computed) {
            switch ($computed->type) {
                case CompiledComputed::CALC:
                    foreach ($refs as &$record) {
                        $arguments = array_map(fn (string $dependency) => $record['#deps'][$dependency], $computed->dependencies);
                        $record[$name] = ($computed->calculate)(...$arguments, ...[$context]);
                    }
                    unset($record);
                    break;

                case CompiledComputed::RELATION:
                    $grouped = $this->fetchChildren($node->entity, $computed->relation, $computed->child, $ids, $computed->where, $computed->orderBy, $context);
                    foreach ($refs as &$record) {
                        $rows = $grouped[$record['#id']] ?? [];
                        $value = $computed->first ? ($rows[0] ?? null) : $rows;
                        $record[$name] = ($computed->transform !== null) ? ($computed->transform)($value, $context) : $value;
                    }
                    unset($record);
                    break;

                case CompiledComputed::RESOLVE:
                    $values = ($computed->resolver)($ids, $context);
                    foreach ($refs as &$record) {
                        $record[$name] = $values[$record['#id']] ?? null;
                    }
                    unset($record);
                    break;
            }
        }
    }

    /**
     * Fetch the (output-ready) records of a relation for a set of owners, grouped by owner-id
     *
     * @param list<int> $ownerIds
     * @param array<string, Order> $order
     * @return array<int, list<array>>
     */
    private function fetchChildren(string $ownerEntity, string $relation, ExposureNode $node, array $ownerIds, ?\Closure $where, array $order, ?ClientContext $context): array
    {
        // Query on the target, joined from the owner
        $qb = $this->entityManager->createQueryBuilder()
            ->from($ownerEntity, 'p')
            ->innerJoin('p.' . $relation, JoinRegistry::ROOT)
            ->select('p.id AS c_owner')
            ->addSelect(JoinRegistry::ROOT . '.id AS c_id')
            ->andWhere('p.id IN (:owners)')
            ->setParameter('owners', $ownerIds);
        $joins = new JoinRegistry($qb, $this->paths, $node->entity);
        $columns = ['c_id' => '#'];
        $types = [];
        $this->addColumns($qb, $joins, $node, [], $columns, $types);

        // Condition and sorting (default: id ascending, the database-order of legacy)
        if ($where !== null) {
            $condition = new Where();
            $where($condition, $context);
            $expression = $this->criteriaBuilder->toExpression($qb, $condition, fn (string $path) => $joins->field($path), $node->entity);
            if ($expression !== null) $qb->andWhere($expression);
        }
        foreach ($order ?: ['id' => Order::Ascending] as $path => $direction) {
            $qb->addOrderBy($joins->field($path), $direction->value);
        }

        // Fetch and enrich (recursive)
        $rows = $this->convert($qb->getQuery()->getScalarResult(), $types);
        $records = array_map(fn (array $row) => $this->assembler->build($row, $columns, $node), $rows);
        $this->enrich($records, $node, $context);

        // Return (grouped by owner)
        $grouped = [];
        foreach ($rows as $i => $row) {
            $grouped[$row['c_owner']][] = $this->assembler->finalize($records[$i], $node, false);
        }
        return $grouped;
    }
}
