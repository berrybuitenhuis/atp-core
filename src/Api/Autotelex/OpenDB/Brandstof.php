<?php

namespace AtpCore\Api\Autotelex\OpenDB;

class Brandstof
{
    public string $brandstofCode;
    public string $soortBrandstof;
    public string $omschrijving;
    public string $mutatiecode;

    public static function getSchema($filename)
    {
        return [
            'brandstofCode' => 1,
            'soortBrandstof' => 8,
            'omschrijving' => 15,
            'mutatiecode' => 1,
        ];
    }
}
