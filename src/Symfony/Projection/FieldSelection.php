<?php

namespace AtpCore\Symfony\Projection;

use AtpCore\Symfony\Resource\ExposureNode;

/**
 * Intersection of the requested fields with the exposure (semantics of legacy getRequestedFields)
 */
final class FieldSelection
{
    /**
     * @param ?array $requested paths in dash- or dot-notation; null or [] = full exposure
     */
    public static function resolve(ExposureNode $exposure, ?array $requested): ExposureNode
    {
        if (empty($requested)) return $exposure;

        // Build tree of requested paths (true = whole subtree)
        $tree = [];
        foreach ($requested as $path) {
            if (!is_string($path) || $path === '') continue;
            $segments = preg_split('/[-.]/', $path);

            // Top-level scalar or computed field: case-insensitive, canonical name
            if (count($segments) === 1) {
                foreach ([...$exposure->fields, ...array_keys($exposure->computed)] as $name) {
                    if (strcasecmp($name, $segments[0]) === 0) {
                        $segments[0] = $name;
                        break;
                    }
                }
            }
            $tree = self::addPath($tree, $segments);
        }

        // Return
        return self::filter($exposure, $tree);
    }

    private static function addPath(array $tree, array $segments): array
    {
        $segment = array_shift($segments);
        if (($tree[$segment] ?? null) === true) return $tree;
        $tree[$segment] = empty($segments) ? true : self::addPath(is_array($tree[$segment] ?? null) ? $tree[$segment] : [], $segments);
        return $tree;
    }

    private static function filter(ExposureNode $node, array $tree): ExposureNode
    {
        // Keep fields (unknown names drop silently)
        $fields = array_values(array_filter($node->fields, fn (string $field) => ($tree[$field] ?? null) === true));

        // Keep relations and collections (case-sensitive): whole subtree or filtered
        $relations = self::filterChildren($node->relations, $tree);
        $collections = self::filterChildren($node->collections, $tree);

        // Keep computed fields as a whole (legacy ignores sub-selection of conv-fields)
        // TODO: dot-notation sub-selection of computed relation-fields (PhoneNumbers.phoneNumber, extension in spec §7.4) is not supported yet
        $computed = array_filter($node->computed, fn (string $name) => isset($tree[$name]), ARRAY_FILTER_USE_KEY);

        // Keep selection-order
        $kept = [...$fields, ...array_keys($relations), ...array_keys($collections), ...array_keys($computed)];
        $order = array_values(array_filter($node->order, fn (string $key) => in_array($key, $kept, true)));

        // Return
        return new ExposureNode($node->entity, $fields, $relations, $collections, $computed, $order);
    }

    /**
     * @param array<string, ExposureNode> $children
     * @return array<string, ExposureNode>
     */
    private static function filterChildren(array $children, array $tree): array
    {
        $kept = [];
        foreach ($children as $name => $child) {
            if (!isset($tree[$name])) continue;
            $kept[$name] = ($tree[$name] === true) ? $child : self::filter($child, $tree[$name]);
            if ($tree[$name] !== true && $kept[$name]->getEmpty()) unset($kept[$name]);
        }
        return $kept;
    }
}
