<?php

namespace AtpCore\Symfony\Doctrine;

use AtpCore\Symfony\Repository\AbstractRepository;

class BaseEntity
{
    public function toResponse(AbstractRepository $repository, $transform = true, $fields = null)
    {
        $record = $repository->getHydrator()->extract($this);
        if ($transform === true && method_exists($repository, 'transformData')) {
            return $repository->transformData($record, $fields);
        } else {
            return $record;
        }
    }
}