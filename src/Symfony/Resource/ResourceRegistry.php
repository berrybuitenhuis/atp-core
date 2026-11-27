<?php

namespace AtpCore\Symfony\Resource;

use AtpCore\Symfony\Context\ClientContext;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * All resources, compiled per client on first use
 */
final class ResourceRegistry
{
    /**
     * @var ?array<class-string<Resource>, Resource> indexed on first use (resources may depend on services that need this registry)
     */
    private ?array $resources = null;

    /**
     * @var array<string, CompiledResource>
     */
    private array $compiled = [];

    /**
     * @param iterable<Resource> $iterable
     */
    public function __construct(
        #[AutowireIterator('atp.resource')] private readonly iterable $iterable,
        private readonly ResourceCompiler $compiler,
    ) {}

    // shortcut: compiled per process (closures can't be serialized); cache only if profiling shows compile cost
    public function get(string $resourceClass, ClientContext $context): CompiledResource
    {
        $key = $resourceClass . '|' . $context->getClient()->name;
        if (!isset($this->compiled[$key])) {
            $resources = $this->resources();
            if (!isset($resources[$resourceClass])) throw new DefinitionException("Unknown resource {$resourceClass}");
            $this->compiled[$key] = $this->compiler->compile($resources[$resourceClass], $context);
        }

        // Return
        return $this->compiled[$key];
    }

    /**
     * Get the resource of an entity (null if the entity has no resource)
     *
     * @return ?class-string<Resource>
     * @throws DefinitionException
     */
    public function forEntity(string $entity): ?string
    {
        $resourceClasses = array_keys(array_filter($this->resources(), fn (Resource $resource) => $resource->entity() === $entity));
        if (count($resourceClasses) > 1) throw new DefinitionException("Multiple resources for $entity: " . implode(', ', $resourceClasses));

        // Return
        return $resourceClasses[0] ?? null;
    }

    /**
     * @return list<class-string<Resource>>
     */
    public function all(): array
    {
        return array_keys($this->resources());
    }

    /**
     * @return array<class-string<Resource>, Resource>
     */
    private function resources(): array
    {
        if ($this->resources === null) {
            $this->resources = [];
            foreach ($this->iterable as $resource) {
                $this->resources[$resource::class] = $resource;
            }
        }
        return $this->resources;
    }
}
