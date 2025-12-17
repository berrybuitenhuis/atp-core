<?php

namespace AtpCore\Api\Autotelex\OpenDB;

class Versnelling
{
    public int $versnellingNummer;
    public string $afkorting;
    public int $aantalVersnellingen;
    public string $omschrijving;
    public string $mutatiecode;
    public string $hash;

    public static function getSchema($filename)
    {
        return [
            'versnellingNummer' => 2,
            'afkorting' => 6,
            'aantalVersnellingen' => 2,
            'omschrijving' => 25,
            'mutatiecode' => 1,
        ];
    }
}
