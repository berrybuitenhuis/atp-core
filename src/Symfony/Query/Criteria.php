<?php

namespace AtpCore\Symfony\Query;

final readonly class Criteria
{
    /**
     * @param list<\Closure> $extraWhere closures fn (Where $w, ClientContext $c) from code
     */
    public function __construct(
        public ?array $filter = null,
        public ?array $defaultFilter = null,
        public ?array $orderBy = null,
        public ?array $groupBy = null,
        public array $extraWhere = [],
        public bool $scoped = true,
    ) {}
}
