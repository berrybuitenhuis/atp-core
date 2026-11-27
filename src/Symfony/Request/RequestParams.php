<?php

namespace AtpCore\Symfony\Request;

use Symfony\Component\HttpFoundation\Request;

/**
 * Query-parameters with the exact semantics of AtpCore\Input::setParams
 */
final readonly class RequestParams
{
    public function __construct(
        public array|false|null $fields = null,
        public ?array $defaultFilter = null,
        public ?array $filter = null,
        public ?array $groupBy = null,
        public ?array $orderBy = null,
        public ?int $limit = null,
        public ?int $offset = null,
        public bool $paginator = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $query = $request->query;

        // Decode JSON-parameters (invalid JSON becomes null)
        $fields = self::decode($query->get('fields'));
        $defaultFilter = self::decode($query->get('defaultFilter'));
        if (is_string($defaultFilter)) $defaultFilter = [$defaultFilter];

        // Set paging
        $limit = $query->has('limit') ? (int) $query->get('limit') : null;
        $page = (int) $query->get('page');
        $offset = ($query->has('page') && $page > 1) ? ($page - 1) * (int) $limit : null;

        // Set paginator (true unless provided with other value than 'true' or 1)
        $paginatorValue = $query->get('paginator');
        $paginator = !$query->has('paginator') || $paginatorValue === 'true' || $paginatorValue == 1;

        // Return
        return new self(
            fields: ($fields === false || is_array($fields)) ? $fields : null,
            defaultFilter: is_array($defaultFilter) ? $defaultFilter : null,
            filter: self::decodeArray($query->get('filter')),
            groupBy: self::decodeArray($query->get('groupBy')),
            orderBy: self::decodeArray($query->get('orderBy')),
            limit: $limit,
            offset: $offset,
            paginator: $paginator,
        );
    }

    private static function decode(?string $value): mixed
    {
        return ($value === null) ? null : json_decode($value, true);
    }

    private static function decodeArray(?string $value): ?array
    {
        $decoded = self::decode($value);
        return is_array($decoded) ? $decoded : null;
    }
}
