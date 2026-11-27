<?php

namespace AtpCore\Symfony\Projection;

use AtpCore\Symfony\Resource\ExposureNode;
use AtpCore\Symfony\Resource\MetadataPaths;

/**
 * Builds records from flat scalar rows and converts them into the legacy output-shape
 *
 * Internal records carry '#id' (identifier) and '#deps' (dependencies of map-fields) until finalize()
 */
final class RowAssembler
{
    /**
     * @var array<string, list<string>> entity => property-order (fields, then associations)
     */
    private array $order = [];

    public function __construct(
        private readonly MetadataPaths $paths,
    ) {}

    /**
     * Column-key of a path inside the record (prefix = relation-path from the selection-root)
     *
     * @param list<string> $prefix
     */
    public static function key(array $prefix, string $name): string
    {
        return empty($prefix) ? $name : implode('.', $prefix) . '.' . $name;
    }

    /**
     * Build an internal record from a flat row
     *
     * @param array<string, string> $columns result-alias => column-key
     * @param list<string> $prefix
     */
    public function build(array $row, array $columns, ExposureNode $node, array $prefix = []): ?array
    {
        $values = [];
        foreach ($columns as $alias => $key) {
            $values[$key] = $row[$alias] ?? null;
        }
        return $this->buildNode($values, $node, $prefix);
    }

    /**
     * Convert an internal record into the output-shape (legacy contract, spec §7.8)
     */
    public function finalize(?array $record, ExposureNode $node, bool $top): ?array
    {
        if ($record === null) return null;

        // Values in selection-order
        $output = [];
        foreach ($node->order as $key) {
            if (isset($node->relations[$key])) {
                $output[$key] = $this->finalize($record[$key], $node->relations[$key], false);
            } elseif (isset($node->collections[$key])) {
                $output[$key] = empty($record[$key]) ? null : $record[$key];
            } elseif (isset($node->computed[$key])) {
                $value = $record[$key];
                if ($value === []) $value = null;
                if (is_object($value) && !$value instanceof \DateTimeInterface) continue;
                $output[$key] = $value;
            } else {
                $output[$key] = $record[$key];
            }
        }
        if (!$top) return $output;

        // Top-level: properties in metadata-order (an empty relation disappears), then virtual fields in selection-order
        $ordered = [];
        $properties = $this->propertyOrder($node->entity);
        foreach ($properties as $property) {
            if (!array_key_exists($property, $output)) continue;
            $isRelation = isset($node->relations[$property]) || isset($node->collections[$property]);
            if ($isRelation && $output[$property] === null) continue;
            $ordered[$property] = $output[$property];
        }
        foreach ($node->order as $key) {
            if (array_key_exists($key, $output) && !in_array($key, $properties, true)) $ordered[$key] = $output[$key];
        }

        // Return
        return $ordered;
    }

    /**
     * @param array<string, mixed> $values column-key => value
     * @param list<string> $prefix
     */
    private function buildNode(array $values, ExposureNode $node, array $prefix): ?array
    {
        // A relation without identifier is null
        $id = $values[self::key($prefix, '#')] ?? null;
        if ($id === null) return null;

        $record = ['#id' => $id, '#deps' => []];
        foreach ($node->fields as $field) {
            $record[$field] = $values[self::key($prefix, $field)] ?? null;
        }
        foreach ($node->relations as $name => $child) {
            $record[$name] = $this->buildNode($values, $child, [...$prefix, $name]);
        }
        foreach ($node->computed as $computed) {
            foreach ($computed->dependencies as $dependency) {
                $record['#deps'][$dependency] = $values[self::key($prefix, '~' . $dependency)] ?? null;
            }
        }

        // Return
        return $record;
    }

    /**
     * @return list<string>
     */
    private function propertyOrder(string $entity): array
    {
        if (!isset($this->order[$entity])) {
            $metadata = $this->paths->metadata($entity);
            $this->order[$entity] = [...$metadata->getFieldNames(), ...$metadata->getAssociationNames()];
        }
        return $this->order[$entity];
    }
}
