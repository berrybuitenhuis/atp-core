<?php

namespace AtpCore\Symfony\Query;

/**
 * Client has no scope-declaration for the resource (closed by default)
 */
final class ClientNotAllowedException extends \RuntimeException
{
    public function __construct(?string $clientIdentifier, string $entity)
    {
        parent::__construct('Client ' . ($clientIdentifier ?? 'unknown') . ' has no access to ' . $entity);
    }
}
