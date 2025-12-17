<?php

namespace AtpCore\Api\Autotelex\OpenDB;

use AtpCore\Error;

class Model
{
    public int $soortVoertuig;
    public int $modelNummer;
    public int $datumPrijslijst;
    public string $modelNaam;
    public ?string $carvariant;
    public int $afleverprijs;
    public int $typeNummer;
    public int $merkNummer;
    public string $mutatiecode;
    public string $hash;

    public static function getSchema($filename)
    {
        switch (\AtpCore\Format::lowercase($filename)) {
            case "model_jr.110":
            case "model_jr.120":
            case "model_jr.130":
            case "model_jr.140":
            case "model_jr.180":
                return [
                    'modelNummer' => 4,
                    'datumPrijslijst' => 8,
                    'modelNaam' => 15,
                    'carvariant' => 15,
                    'afleverprijs' => 5,
                    'typeNummer' => 4,
                    'merkNummer' => 4,
                    'mutatiecode' => 1,
                ];
            case "model_jr.150":
                $base = self::getSchema("model_jr.110");
                $base['modelNummer'] = 5;
                $base['modelNaam'] = 30;
                return $base;
            default:
                return new Error(messages: ["No fields specified for file $filename"]);
        }
    }
}
