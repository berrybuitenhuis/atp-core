<?php

namespace AtpCore\Symfony\Query;

final readonly class ProjectionResult
{
    /**
     * @param ?list<array> $rows null when fields === false (paginator only)
     * @param ?array $paginator {records, pages, currentPage, recordsPage}, null without paginator
     */
    public function __construct(
        public ?array $rows,
        public ?array $paginator,
    ) {}
}
