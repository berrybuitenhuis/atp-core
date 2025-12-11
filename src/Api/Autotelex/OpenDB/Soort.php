<?php

namespace AtpCore\Api\Autotelex\OpenDB;

class Soort
{
    public int $soortNummer;
    public string $soortNaam;
    public string $mutatiecode;

    public static function getSchema($filename)
    {
        return [
            'soortNummer' => 3,
            'soortNaam' => 75,
            'mutatiecode' => 1,
        ];
    }
}
