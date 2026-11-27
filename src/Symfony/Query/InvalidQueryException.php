<?php

namespace AtpCore\Symfony\Query;

/**
 * Invalid request-criteria (unknown field, operator, alias or default-filter)
 */
final class InvalidQueryException extends \RuntimeException
{
    /**
     * @param array $messages messages for the legacy-envelope
     * @param int $status HTTP-status (400, or 200 where legacy returned an error-envelope)
     */
    public function __construct(
        private readonly array $messages,
        private readonly int $status = 400,
    ) {
        parent::__construct(implode(', ', array_map('strval', $messages)));
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
