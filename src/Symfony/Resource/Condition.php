<?php

namespace AtpCore\Symfony\Resource;

final readonly class Condition
{
    public function __construct(
        public string $operator,
        public ?string $path = null,
        public mixed $value = null,
        public array $parameters = [],
    ) {}
}
