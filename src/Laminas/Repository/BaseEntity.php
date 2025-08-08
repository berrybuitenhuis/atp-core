<?php

namespace AtpCore\Laminas\Repository;

class BaseEntity {
    public function toDataSet(AbstractRepository $repository, $fields = null)
    {
        // Return result
        $record = $repository->getHydrator()->extract($this);
        if (method_exists($repository, 'transformData')) return $repository->transformData($record, $fields);
        else return $record;
    }
}
