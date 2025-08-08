<?php

namespace AtpCore\Laminas\Doctrine;

use AtpCore\Laminas\Repository\AbstractRepository;

class BaseEntity {
    public function toResponse(AbstractRepository $repository, $fields = null)
    {
        // Return result
        $record = $repository->getHydrator()->extract($this);
        if (method_exists($repository, 'transformData')) return $repository->transformData($record, $fields);
        else return $record;
    }
}
