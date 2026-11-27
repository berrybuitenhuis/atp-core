<?php

namespace AtpCore\Symfony\Resource;

use AtpCore\Symfony\Context\ClientContext;
use AtpCore\Symfony\Context\Clients;

/**
 * Compiles and validates a resource-definition per client against the Doctrine-metadata
 */
final class ResourceCompiler
{
    public function __construct(
        private readonly MetadataPaths $paths,
    ) {}

    /**
     * Compile for the client of the context (the context is also used to validate scope- and filter-closures)
     *
     * @throws DefinitionException
     */
    public function compile(Resource $resource, ClientContext $context): CompiledResource
    {
        $client = $context->getClient();
        try {
            $definition = ResolvedDefinition::read($resource, $client);
            $root = $this->paths->metadata($definition->entity);

            // Validate scopes and named filters
            foreach ([...($definition->scopes ?? []), ...$definition->filters] as $build) {
                $this->validateClosure($definition->entity, $build, $context);
            }

            // Set aliases: every association of the root automatically, plus the custom ones
            $aliases = [];
            foreach ($root->getAssociationNames() as $association) {
                $aliases[$association] = new AliasJoin($association);
            }
            foreach ($definition->customAliases as $alias => [$path, $on]) {
                if ($root->hasField($alias) || $root->hasAssociation($alias)) {
                    throw new \InvalidArgumentException("Custom alias \"$alias\" collides with a property of $definition->entity");
                }
                $resolved = $this->paths->resolve($definition->entity, $path);
                [$metadata, $last] = end($resolved);
                if (!$metadata->hasAssociation($last)) throw new \InvalidArgumentException("Custom alias \"$alias\" must end on a relation");
                if ($on !== null) $this->validateOn($metadata->getAssociationTargetClass($last), $on);
                $aliases[$alias] = new AliasJoin($path, $on);
            }

            // Validate default order
            foreach (array_keys($definition->defaultOrder) as $path) {
                $resolved = $this->paths->resolve($definition->entity, $path);
                [$metadata, $last] = end($resolved);
                if (!$metadata->hasField($last)) throw new \InvalidArgumentException("Default order \"$path\" must end on a field");
            }

            // Validate updatable fields
            foreach ($definition->updatable ?? [] as $field) {
                if (!$root->hasField($field) && !$root->hasAssociation($field)) {
                    throw new \InvalidArgumentException("Updatable \"$field\" is not a property of $definition->entity");
                }
            }

            // Return
            return new CompiledResource(
                entity: $definition->entity,
                exposure: $this->compileExposure($definition->entity, $definition->exposure, $context),
                scopes: $definition->scopes,
                aliases: $aliases,
                filters: $definition->filters,
                defaultOrder: $definition->defaultOrder,
                updatable: $definition->updatable,
            );
        } catch (\InvalidArgumentException|DefinitionException $e) {
            throw new DefinitionException($resource::class . " ($client->name): " . $e->getMessage(), previous: $e);
        }
    }

    /**
     * Compile a selection (same format as expose) on an entity, for queries without resource
     *
     * @throws DefinitionException
     */
    public function compileSelection(string $entity, array $selection, ?ClientContext $context = null): ExposureNode
    {
        try {
            return $this->compileExposure($entity, $selection, $context);
        } catch (\InvalidArgumentException $e) {
            throw new DefinitionException("Selection on $entity: " . $e->getMessage(), previous: $e);
        }
    }

    private function compileExposure(string $entity, array $selection, ?ClientContext $context): ExposureNode
    {
        $metadata = $this->paths->metadata($entity);
        $fields = [];
        $relations = [];
        $collections = [];
        $computed = [];
        $order = [];
        foreach ($selection as $key => $value) {
            if ($value === true) {
                // Field
                if ($metadata->hasAssociation($key)) throw new \InvalidArgumentException("Relation \"$key\" on $entity needs a selection");
                if (!$metadata->hasField($key)) throw new \InvalidArgumentException("Unknown field \"$key\" on $entity");
                $fields[] = $key;
                $order[] = $key;
            } elseif ($value instanceof ComputedField) {
                // Computed field (the key is its name), skipped for other clients than its for()
                if ($value->getFor() !== null && $context !== null && !Clients::match($value->getFor(), $context->getClient())) continue;
                $computed[$key] = $this->compileComputed($entity, $key, $value, $context);
                $order[] = $key;
            } else {
                // Relation (to-one joined, to-many as batch)
                if (!$metadata->hasAssociation($key)) throw new \InvalidArgumentException("\"$key\" on $entity is not a relation");
                $child = $this->compileExposure($metadata->getAssociationTargetClass($key), $value, $context);
                if ($metadata->isSingleValuedAssociation($key)) $relations[$key] = $child;
                else $collections[$key] = $child;
                $order[] = $key;
            }
        }

        // Return
        return new ExposureNode($entity, $fields, $relations, $collections, $computed, $order);
    }

    private function compileComputed(string $entity, string $name, ComputedField $field, ?ClientContext $context): CompiledComputed
    {
        // Calculated (alias, calc)
        if ($field instanceof CalculatedField) {
            foreach ($field->getDependencies() as $dependency) {
                $resolved = $this->paths->resolve($entity, $dependency);
                [$metadata, $last] = end($resolved);
                if (!$metadata->hasField($last)) throw new \InvalidArgumentException("Dependency \"$dependency\" of \"$name\" must end on a field");
            }
            return new CompiledComputed(CompiledComputed::CALC, $name, calculate: $field->getCalculate(), dependencies: $field->getDependencies());
        }

        // Relation (a to-one relation gives one record)
        if ($field instanceof RelationField) {
            $relation = $field->getRelation();
            $metadata = $this->paths->metadata($entity);
            if (!$metadata->hasAssociation($relation)) throw new \InvalidArgumentException("\"$relation\" of \"$name\" is not a relation of $entity");
            $target = $metadata->getAssociationTargetClass($relation);
            foreach (array_keys($field->getOrderBy()) as $path) {
                $resolved = $this->paths->resolve($target, $path);
                [$targetMetadata, $last] = end($resolved);
                if (!$targetMetadata->hasField($last)) throw new \InvalidArgumentException("Order \"$path\" of \"$name\" must end on a field");
            }
            if ($field->getWhere() !== null) $this->validateClosure($target, $field->getWhere(), $context);

            // TODO: a to-one relation-field without where() could be joined in the main query instead of a separate batch-query (performance)
            return new CompiledComputed(
                CompiledComputed::RELATION,
                $name,
                relation: $relation,
                child: $this->compileExposure($target, $field->getSelection(), $context),
                where: $field->getWhere(),
                orderBy: $field->getOrderBy(),
                first: $field->getFirst() || $metadata->isSingleValuedAssociation($relation),
                transform: $field->getTransform(),
            );
        }

        // Resolved
        if ($field instanceof ResolvedField) {
            return new CompiledComputed(CompiledComputed::RESOLVE, $name, resolver: $field->getResolver());
        }

        throw new \InvalidArgumentException("Unknown computed field \"$name\"");
    }

    private function validateClosure(string $entity, \Closure $build, ?ClientContext $context): void
    {
        $where = new Where();
        try {
            $build($where, $context);
        } catch (\Throwable) {
            // shortcut: closures that fail on this context are not validated here; PHPStan and runtime still check them
            return;
        }
        $this->validateConditions($entity, $where);
    }

    private function validateConditions(string $entity, Where $where): void
    {
        foreach ($where->conditions() as $condition) {
            if ($condition->operator === 'or') {
                foreach ($condition->value as $branch) $this->validateConditions($entity, $branch);
            } elseif ($condition->operator === 'exists') {
                $resolved = $this->paths->resolve($entity, $condition->path);
                [$metadata, $relation] = end($resolved);
                if (!$metadata->hasAssociation($relation) || $metadata->isSingleValuedAssociation($relation)) {
                    throw new \InvalidArgumentException("exists() needs a to-many relation, \"$condition->path\" on $entity is not");
                }
                $target = $metadata->getAssociationTargetClass($relation);
                foreach (array_keys($condition->value) as $path) $this->paths->resolve($target, $path);
            } elseif ($condition->path !== null) {
                $this->paths->resolve($entity, $condition->path);
            }
        }
    }

    private function validateOn(string $entity, \Closure $on): void
    {
        $where = new Where();
        $on($where);
        foreach ($where->conditions() as $condition) {
            if ($condition->path === null) continue;
            // shortcut: join-conditions only on direct fields of the aliased entity (DQL WITH can't use later joins)
            if (str_contains($condition->path, '.')) throw new \InvalidArgumentException("Join-condition \"$condition->path\" must be a direct field");
            $this->paths->resolve($entity, $condition->path);
        }
    }
}
