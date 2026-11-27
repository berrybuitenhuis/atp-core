<?php

namespace AtpCore\Symfony\Resource;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Resolves property-paths (dot-notation) against the Doctrine-metadata
 */
final class MetadataPaths
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function metadata(string $entity): ClassMetadata
    {
        return $this->entityManager->getClassMetadata($entity);
    }

    /**
     * Resolve a path segment by segment; every segment but the last must be an association
     *
     * @return list<array{ClassMetadata, string}> per segment: metadata of the entity it belongs to and the property-name
     * @throws \InvalidArgumentException
     */
    public function resolve(string $entity, string $path): array
    {
        $segments = explode('.', $path);
        $metadata = $this->metadata($entity);
        $resolved = [];
        foreach ($segments as $i => $segment) {
            $isField = $metadata->hasField($segment);
            $isAssociation = $metadata->hasAssociation($segment);
            if (!$isField && !$isAssociation) {
                throw new \InvalidArgumentException("Unknown field \"{$segment}\" on {$metadata->getName()} in path \"$path\"");
            }
            $resolved[] = [$metadata, $segment];

            // Step into association (unless last segment)
            if ($i < count($segments) - 1) {
                if (!$isAssociation) throw new \InvalidArgumentException("\"{$segment}\" on {$metadata->getName()} is not a relation in path \"$path\"");
                $metadata = $this->metadata($metadata->getAssociationTargetClass($segment));
            }
        }

        // Return
        return $resolved;
    }
}
