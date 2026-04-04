<?php

namespace AtpCore\Symfony\Hydrator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

class DoctrineHydrator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Extract values from an object into an array
     *
     * @param object $object
     * @return array
     */
    public function extract(object $object): array
    {
        $metadata = $this->entityManager->getClassMetadata(get_class($object));
        $data = [];

        // Extract mapped fields
        foreach ($metadata->getFieldNames() as $fieldName) {
            $data[$fieldName] = $metadata->getFieldValue($object, $fieldName);
        }

        // Extract associations
        foreach ($metadata->getAssociationNames() as $fieldName) {
            $reflProperty = $metadata->getReflectionProperty($fieldName);
            $data[$fieldName] = $reflProperty->getValue($object);
        }

        return $data;
    }

    /**
     * Hydrate an object from an array of data
     *
     * @param array $data
     * @param object $object
     * @return object
     */
    public function hydrate(array $data, object $object): object
    {
        $metadata = $this->entityManager->getClassMetadata(get_class($object));

        foreach ($data as $field => $value) {
            if ($metadata->hasField($field)) {
                $this->hydrateField($metadata, $object, $field, $value);
            } elseif ($metadata->hasAssociation($field)) {
                $this->hydrateAssociation($metadata, $object, $field, $value);
            }
        }

        return $object;
    }

    private function hydrateField(ClassMetadata $metadata, object $object, string $field, mixed $value): void
    {
        // Use setter if available
        $setter = 'set' . ucfirst($field);
        if (method_exists($object, $setter)) {
            $object->$setter($value);
        } else {
            $metadata->setFieldValue($object, $field, $value);
        }
    }

    private function hydrateAssociation(ClassMetadata $metadata, object $object, string $field, mixed $value): void
    {
        $setter = 'set' . ucfirst($field);
        $targetEntity = $metadata->getAssociationTargetClass($field);

        if ($metadata->isSingleValuedAssociation($field)) {
            // ToOne association
            if ($value === null) {
                if (method_exists($object, $setter)) {
                    $object->$setter(null);
                } else {
                    $metadata->getReflectionProperty($field)->setValue($object, null);
                }
            } elseif (is_object($value)) {
                if (method_exists($object, $setter)) {
                    $object->$setter($value);
                } else {
                    $metadata->getReflectionProperty($field)->setValue($object, $value);
                }
            } else {
                // Scalar value: resolve to entity reference
                $reference = $this->entityManager->getReference($targetEntity, $value);
                if (method_exists($object, $setter)) {
                    $object->$setter($reference);
                } else {
                    $metadata->getReflectionProperty($field)->setValue($object, $reference);
                }
            }
        } else {
            // ToMany association
            $this->hydrateToManyAssociation($metadata, $object, $field, $value, $targetEntity);
        }
    }

    private function hydrateToManyAssociation(ClassMetadata $metadata, object $object, string $field, mixed $value, string $targetEntity): void
    {
        if ($value === null) {
            return;
        }

        if ($value instanceof Collection) {
            $metadata->getReflectionProperty($field)->setValue($object, $value);
            return;
        }

        if (is_array($value)) {
            $collection = new ArrayCollection();
            foreach ($value as $item) {
                if (is_object($item)) {
                    $collection->add($item);
                } else {
                    $collection->add($this->entityManager->getReference($targetEntity, $item));
                }
            }
            $metadata->getReflectionProperty($field)->setValue($object, $collection);
        }
    }
}