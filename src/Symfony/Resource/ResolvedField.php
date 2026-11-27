<?php

namespace AtpCore\Symfony\Resource;

/**
 * Field from a resolver, one call per selection-level (see ComputedField::resolve())
 */
final class ResolvedField extends ComputedField
{
    public function __construct(
        private readonly \Closure $resolver,
    ) {}

    public function getResolver(): \Closure
    {
        return $this->resolver;
    }
}
