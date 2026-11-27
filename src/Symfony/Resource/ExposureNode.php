<?php

namespace AtpCore\Symfony\Resource;

/**
 * Validated exposure-tree of one selection-level
 */
final readonly class ExposureNode
{
    /**
     * @param class-string $entity
     * @param list<string> $fields
     * @param array<string, ExposureNode> $relations to-one relations (joined in the query)
     * @param array<string, ExposureNode> $collections to-many relations (one batch-query per relation)
     * @param array<string, CompiledComputed> $computed
     * @param list<string> $order output-keys in selection-order
     */
    public function __construct(
        public string $entity,
        public array $fields,
        public array $relations,
        public array $collections = [],
        public array $computed = [],
        public array $order = [],
    ) {}

    public function getEmpty(): bool
    {
        return empty($this->fields) && empty($this->relations) && empty($this->collections) && empty($this->computed);
    }
}
