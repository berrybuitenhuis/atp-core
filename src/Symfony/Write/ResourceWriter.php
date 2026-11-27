<?php

namespace AtpCore\Symfony\Write;

use AtpCore\Error;
use AtpCore\Symfony\Context\ClientContext;
use AtpCore\Symfony\Query\ClientNotAllowedException;
use AtpCore\Symfony\Query\InvalidQueryException;
use AtpCore\Symfony\Query\QueryFactory;
use AtpCore\Symfony\Repository\AbstractRepository;
use AtpCore\Symfony\Resource\ResourceRegistry;

/**
 * Write-path on the existing repositories, with a scope-check beforehand
 */
final class ResourceWriter
{
    public function __construct(
        private readonly QueryFactory $queries,
        private readonly ResourceRegistry $registry,
    ) {}

    /**
     * Create (no whitelist, like legacy) and return the full exposure of the client
     */
    public function create(string $resource, AbstractRepository $repository, array $data, ClientContext $context): array|Error
    {
        try {
            $this->queries->create($resource, $context)->assertAllowed();

            // Create
            $object = $repository->create($data);
            if (Error::isError($object)) return $object;

            // Return
            return $this->response($resource, $object->getId(), $context);
        } catch (InvalidQueryException $e) {
            return new Error(messages: $e->getMessages(), statusCode: $e->getStatus());
        } catch (ClientNotAllowedException $e) {
            return new Error(messages: ['clientNotAllowed' => $e->getMessage()], statusCode: 403);
        }
    }

    /**
     * Update (only rows within scope; the repository applies the whitelist of the client) and return the full exposure of the client
     */
    public function update(string $resource, AbstractRepository $repository, int $id, array $data, ClientContext $context): array|Error
    {
        try {
            $error = $this->notFoundOutsideScope($resource, $id, $context);
            if ($error !== null) return $error;

            // Update
            $result = $repository->update($id, $data, true);
            if (Error::isError($result)) return $result;

            // Return
            return $this->response($resource, $id, $context);
        } catch (InvalidQueryException $e) {
            return new Error(messages: $e->getMessages(), statusCode: $e->getStatus());
        } catch (ClientNotAllowedException $e) {
            return new Error(messages: ['clientNotAllowed' => $e->getMessage()], statusCode: 403);
        }
    }

    /**
     * Delete (only rows within scope); soft-delete is an update of status
     */
    public function delete(string $resource, AbstractRepository $repository, int $id, bool $remove, ClientContext $context): true|Error
    {
        try {
            $error = $this->notFoundOutsideScope($resource, $id, $context);
            if ($error !== null) return $error;

            // Delete
            $result = $repository->delete($id, $remove);
            if (Error::isError($result)) return $result;

            // Return
            return true;
        } catch (InvalidQueryException $e) {
            return new Error(messages: $e->getMessages(), statusCode: $e->getStatus());
        } catch (ClientNotAllowedException $e) {
            return new Error(messages: ['clientNotAllowed' => $e->getMessage()], statusCode: 403);
        }
    }

    /**
     * Check that the row is within the scope of the client (the update-whitelist is checked by the repository itself)
     */
    private function notFoundOutsideScope(string $resource, int $id, ClientContext $context): ?Error
    {
        if ($this->queries->create($resource, $context)->byId($id)->exists()) return null;

        // Return
        return new Error(messages: ['notFound' => $this->registry->get($resource, $context)->entity . ' not found']);
    }

    /**
     * Response after a write: the record may have left the scope (e.g. status=false), so unscoped
     */
    private function response(string $resource, int $id, ClientContext $context): array|Error
    {
        return $this->queries->create($resource, $context)->unscoped()->byId($id)->get(null);
    }
}
