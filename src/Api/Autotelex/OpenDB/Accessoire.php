<?php

namespace AtpCore\Api\Autotelex\OpenDB;

use AtpCore\Error;

class Accessoire
{
    public int $accessoireNummer;
    public string $accessoireNaam;
    public string $restwaardeVerhogend110;
    public string $restwaardeVerhogend120;
    public string $restwaardeVerhogend130;
    public string $restwaardeVerhogend140;
    public string $restwaardeVerhogend150;
    public string $restwaardeVerhogend160;
    public string $restwaardeVerhogend180;
    public string $accessoireNaamLang;
    public string $mutatiecode;

    public static function getSchema($filename)
    {
        switch (\AtpCore\Format::lowercase($filename)) {
            case "acc.dat":
                return [
                    'accessoireNummer' => 5,
                    'accessoireNaam' => 25,
                    'mutatiecode' => 1,
                ];
            case "acc.dat.200911.ext":
                $base = self::getSchema("acc.dat");
                unset($base['mutatiecode']);
                $fields = [
                    'restwaardeVerhogend110' => 1,
                    'restwaardeVerhogend120' => 1,
                    'restwaardeVerhogend130' => 1,
                    'restwaardeVerhogend140' => 1,
                    'restwaardeVerhogend150' => 1,
                    'restwaardeVerhogend160' => 1,
                    'restwaardeVerhogend180' => 1,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "acc.dat.201111.ext":
                $base = self::getSchema("acc.dat.200911.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'accessoireNaamLang' => 50,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            default:
                return new Error(messages: ["No fields specified for file $filename"]);
        }
    }
}
