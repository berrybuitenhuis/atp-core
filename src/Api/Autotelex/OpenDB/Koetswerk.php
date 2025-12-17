<?php

namespace AtpCore\Api\Autotelex\OpenDB;

class Koetswerk
{
    public string $koetswerkCode;
    public string $omschrijving;
    public string $mutatiecode;
    public string $hash;

    public static function getSchema($filename)
    {
        return [
            'koetswerkCode' => 8,
            'omschrijving' => 15,
            'mutatiecode' => 1,
        ];
    }
}
