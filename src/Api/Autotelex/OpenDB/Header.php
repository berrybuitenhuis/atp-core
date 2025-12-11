<?php

namespace AtpCore\Api\Autotelex\OpenDB;

class Header
{
    public int $records;
    public int $changed;
    public int $deleted;

    public static function getSchema($filename)
    {
        return [
            'records' => 6,
            'changed' => 6,
            'deleted' => 6,
        ];
    }
}
