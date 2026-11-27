<?php

namespace AtpCore\Symfony\Resource;

use Doctrine\Common\Collections\Order;

/**
 * Raw definition of a resource for one client (before validation against Doctrine-metadata)
 */
final readonly class ResolvedDefinition
{
    /**
     * @param class-string $entity
     * @param ?list<\Closure> $scopes null = client not allowed
     * @param array<string, \Closure> $filters
     * @param ?list<string> $updatable null = no whitelist
     * @param array<string, array{string, ?\Closure}> $customAliases
     * @param array<string, Order> $defaultOrder
     */
    public function __construct(
        public string $entity,
        public array $exposure,
        public ?array $scopes,
        public array $filters,
        public ?array $updatable,
        public array $customAliases,
        public array $defaultOrder,
    ) {}

    /**
     * Read all parts of a resource for one client
     */
    public static function read(Resource $resource, \UnitEnum $client): self
    {
        $resource->expose($exposure = new Exposure());
        $resource->filters($filters = new Filters());
        $resource->writes($writes = new Writes());
        $resource->customAliases($aliases = new Aliases());

        return new self(
            entity: $resource->entity(),
            exposure: $exposure->resolve($client),
            scopes: self::scopes($resource->scope($client)),
            filters: $filters->resolve($client),
            updatable: $writes->resolve($client),
            customAliases: $aliases->all(),
            defaultOrder: $resource->defaultOrder($client),
        );
    }

    /**
     * @return ?list<\Closure> null = client not allowed, [] = no restriction
     */
    private static function scopes(Scope $scope): ?array
    {
        if (!$scope->getAllowed()) return null;
        return ($scope->getCondition() === null) ? [] : [$scope->getCondition()];
    }
}
