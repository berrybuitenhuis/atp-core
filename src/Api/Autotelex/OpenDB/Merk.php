<?php

namespace AtpCore\Api\Autotelex\OpenDB;

class Merk
{
    public int $merkNummer;
    public string $merkNaam;
    public string $mutatiecode;

    public static function getSchema($filename)
    {
        return [
            'merkNummer' => 4,
            'merkNaam' => 20,
            'mutatiecode' => 1,
        ];
    }
}
