<?php

namespace AtpCore\Api\Autotelex\OpenDB;

class Aandrijving
{
    public string $aandrijvingCode;
    public string $omschrijving;
    public string $mutatiecode;

    public static function getSchema($filename)
    {
        return [
            'aandrijvingCode' => 8,
            'omschrijving' => 15,
            'mutatiecode' => 1,
        ];
    }
}
