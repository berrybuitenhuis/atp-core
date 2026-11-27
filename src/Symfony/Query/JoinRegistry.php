<?php

namespace AtpCore\Symfony\Query;

use AtpCore\Symfony\Resource\MetadataPaths;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

/**
 * One left-join per unique path, shared by projection, scope, filter and sorting
 */
final class JoinRegistry
{
    public const ROOT = 'f';

    /**
     * @var array<string, string> join-key => DQL-alias
     */
    private array $joins = [];

    private bool $hasToMany = false;

    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly MetadataPaths $paths,
        private readonly string $rootEntity,
    ) {}

    /**
     * Get DQL-expression for a property-path from the root (joins the associations on the way)
     *
     * @throws \InvalidArgumentException
     */
    public function field(string $path): string
    {
        $resolved = $this->paths->resolve($this->rootEntity, $path);
        [, $last] = array_pop($resolved);

        // Join associations
        $alias = self::ROOT;
        $prefix = [];
        foreach ($resolved as [$metadata, $segment]) {
            $prefix[] = $segment;
            $alias = $this->join($alias, $segment, $metadata, implode('.', $prefix), implode('_', $prefix));
        }

        // Return
        return "{$alias}.{$last}";
    }

    /**
     * Join a (filter-)alias: the path from the root, with the alias-name as DQL-alias
     *
     * @param ?string $with DQL join-condition
     */
    public function alias(string $name, string $path, ?string $with = null): string
    {
        $resolved = $this->paths->resolve($this->rootEntity, $path);
        [$lastMetadata, $last] = array_pop($resolved);

        // Join associations before the aliased one
        $alias = self::ROOT;
        $prefix = [];
        foreach ($resolved as [$metadata, $segment]) {
            $prefix[] = $segment;
            $alias = $this->join($alias, $segment, $metadata, implode('.', $prefix), implode('_', $prefix));
        }

        // Join aliased association (same key as a path-join when name and path are equal, so it is shared)
        $key = ($name === $path && $with === null) ? $path : "alias:{$name}";
        return $this->join($alias, $last, $lastMetadata, $key, $name, $with);
    }

    public function getRootEntity(): string
    {
        return $this->rootEntity;
    }

    public function getHasToMany(): bool
    {
        return $this->hasToMany;
    }

    private function join(string $parentAlias, string $association, ClassMetadata $metadata, string $key, string $alias, ?string $with = null): string
    {
        if (!isset($this->joins[$key])) {
            if ($with === null) {
                $this->queryBuilder->leftJoin("{$parentAlias}.{$association}", $alias);
            } else {
                $this->queryBuilder->leftJoin("{$parentAlias}.{$association}", $alias, 'WITH', $with);
            }
            if ($metadata->isCollectionValuedAssociation($association)) $this->hasToMany = true;
            $this->joins[$key] = $alias;
        }

        // Return
        return $this->joins[$key];
    }
}
