<?php

namespace AtpCore\Symfony\Resource;

final readonly class AliasJoin
{
    /**
     * @param string $path path from the root-entity, ending on an association
     * @param ?\Closure(Where): mixed $on join-condition, paths relative to the aliased entity
     */
    public function __construct(
        public string $path,
        public ?\Closure $on = null,
    ) {}
}
