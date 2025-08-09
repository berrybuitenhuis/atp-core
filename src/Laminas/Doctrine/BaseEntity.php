<?php

namespace AtpCore\Laminas\Doctrine;

use AtpCore\Laminas\Repository\AbstractRepository;

class BaseEntity {
    public function toResponse(AbstractRepository $repository, $fields = null, $transform = true)
    {
        // Return result
        $record = $repository->getHydrator()->extract($this);
        if ($transform === true && method_exists($repository, 'transformData')) {
            return $repository->transformData($record, $fields);
        } else {
            return $record;
        }
    }
}
