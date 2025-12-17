<?php

namespace AtpCore\Api\Autotelex\OpenDB;

use AtpCore\Error;

class Uitvoering
{
    public int $soortVoertuig;
    public int $uitvoeringNummer;
    public string $uitvoering;
    public ?int $aantalDeuren = null;
    public ?string $koetswerkCode = null;
    public ?string $aandrijvingCode = null;
    public ?string $automaat = null;
    public int $gewicht;
    public ?string $turbo = null;
    public int $motorvermogen;
    public int $motorinhoud;
    public int $aantalCilinders;
    public ?string $emissieCode = null;
    public string $brandstof;
    public int $modelNummer;
    public int $topsnelheid;
    public int $acceleratie;
    public ?int $aantalPersonen = null;
    public ?int $motorvermogenTpm = null;
    public ?int $koppel = null;
    public ?int $koppelTpm = null;
    public ?int $tankinhoud = null;
    public ?int $verbruikBinnen = null;
    public ?int $verbruikBuiten = null;
    public ?int $verbruikGemiddeld = null;
    public int $geleverdVan;
    public ?int $geleverdTot;
    public int $carrosserieGeleverdVan;
    public ?int $carrosserieGeleverdTot;
    public string $uitvoeringLang;
    public ?string $uitvoeringAlgemeen = null;
    public ?string $fabriekscode = null;
    public ?string $segmentCode = null;
    public ?int $accuCapaciteit = null;
    public ?int $actieradius = null;
    public ?int $verbruikKWh = null;
    public ?string $externOpladen = null;
    public ?int $systeemvermogen = null;
    public ?int $vermogenElektromotor1 = null;
    public ?int $vermogenElektromotor2 = null;
    public ?int $maxLaadsnelheidAC = null;
    public ?int $maxLaadsnelheidDC = null;
    public ?int $minLaadtijdUurAC = null;
    public ?int $minLaadtijdMinutenAC = null;
    public ?int $minLaadtijdVanAC = null;
    public ?int $minLaadtijdTotAC = null;
    public ?int $minLaadtijdUurDC = null;
    public ?int $minLaadtijdMinutenDC = null;
    public ?int $minLaadtijdVanDC = null;
    public ?int $minLaadtijdTotDC = null;
    public ?int $actieradiusPraktijk = null;
    public ?string $zonneAuto = null;
    public ?int $laadVermogen = null;
    public ?int $wielbasis = null;
    public ?int $hoogteLaadruimte = null;
    public ?string $geelKenteken = null;
    public ?int $laadVolume = null;
    public ?int $takt = null;
    public ?string $versnellingCode = null;
    public ?string $import = null;
    public ?string $nakedBike = null;
    public string $mutatiecode;
    public string $hash;

    public static function getSchema($filename)
    {
        switch (\AtpCore\Format::lowercase($filename)) {
            case "uitv.110":
                return [
                    'uitvoeringNummer' => 6,
                    'uitvoering' => 20,
                    'aantalDeuren' => 1,
                    'koetswerkCode' => 8,
                    'aandrijvingCode' => 8,
                    'automaat' => 1,
                    'gewicht' => 5,
                    'turbo' => 1,
                    'motorvermogen' => 4,
                    'motorinhoud' => 4,
                    'aantalCilinders' => 2,
                    'emissieCode' => 5,
                    'brandstof' => 1,
                    'modelNummer' => 4,
                    'mutatiecode' => 1,
                ];
            case "uitv.110.ext":
                $base = self::getSchema("uitv.110");
                unset($base['mutatiecode']);
                $fields = [
                    'topsnelheid' => 3,
                    'acceleratie' => 3,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.110.200806.ext":
                $base = self::getSchema("uitv.110.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'aantalPersonen' => 2,
                    'motorvermogenTpm' => 5,
                    'koppel' => 4,
                    'koppelTpm' => 5,
                    'tankinhoud' => 3,
                    'verbruikBinnen' => 4,
                    'verbruikBuiten' => 4,
                    'verbruikGemiddeld' => 4,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.110.200807.ext":
                $base = self::getSchema("uitv.110.200806.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'geleverdVan' => 8,
                    'geleverdTot' => 8,
                    'carrosserieGeleverdVan' => 8,
                    'carrosserieGeleverdTot' => 8,
                    'uitvoeringLang' => 50,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.110.201203.ext":
                $base = self::getSchema("uitv.110.200807.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'uitvoeringAlgemeen' => 50,
                    'fabriekscode' => 20,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.110.201906.ext":
                $base = self::getSchema("uitv.110.201203.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'segmentCode' => 1,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.110.202002.ext":
                $base = self::getSchema("uitv.110.201906.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'accuCapaciteit' => 4,
                    'actieradius' => 4,
                    'verbruikKWh' => 4,
                    'externOpladen' => 1,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.110.202109.ext":
                $base = self::getSchema("uitv.110.202002.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'systeemvermogen' => 4,
                    'vermogenElektromotor1' => 4,
                    'vermogenElektromotor2' => 4,
                    'maxLaadsnelheidAC' => 4,
                    'maxLaadsnelheidDC' => 4,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.110.202311.ext":
                $base = self::getSchema("uitv.110.202109.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'minLaadtijdUurAC' => 3,
                    'minLaadtijdMinutenAC' => 3,
                    'minLaadtijdVanAC' => 3,
                    'minLaadtijdTotAC' => 3,
                    'minLaadtijdUurDC' => 3,
                    'minLaadtijdMinutenDC' => 3,
                    'minLaadtijdVanDC' => 3,
                    'minLaadtijdTotDC' => 3,
                    'actieradiusPraktijk' => 4,
                    'zonneAuto' => 1,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.120":
            case "uitv.130":
            case "uitv.140":
            case "uitv.180":
                return [
                    'uitvoeringNummer' => 6,
                    'uitvoering' => 20,
                    'aantalDeuren' => 1,
                    'koetswerkCode' => 8,
                    'automaat' => 1,
                    'turbo' => 1,
                    'gewicht' => 5,
                    'laadVermogen' => 5,
                    'wielbasis' => 3,
                    'hoogteLaadruimte' => 3,
                    'motorvermogen' => 4,
                    'motorinhoud' => 4,
                    'aantalCilinders' => 2,
                    'brandstof' => 1,
                    'geelKenteken' => 1,
                    'modelNummer' => 4,
                    'mutatiecode' => 1,
                ];
            case "uitv.120.ext":
            case "uitv.130.ext":
            case "uitv.140.ext":
            case "uitv.180.ext":
                $base = self::getSchema("uitv.120");
                unset($base['mutatiecode']);
                $fields = [
                    'topsnelheid' => 3,
                    'acceleratie' => 3,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.120.200807.ext":
            case "uitv.130.200807.ext":
            case "uitv.140.200807.ext":
            case "uitv.180.200807.ext":
                $base = self::getSchema("uitv.120.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'geleverdVan' => 8,
                    'geleverdTot' => 8,
                    'carrosserieGeleverdVan' => 8,
                    'carrosserieGeleverdTot' => 8,
                    'uitvoeringLang' => 50,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.120.201701.ext":
            case "uitv.130.201701.ext":
            case "uitv.140.201701.ext":
                $base = self::getSchema("uitv.120.200807.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'fabriekscode' => 20,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.120.201906.ext":
            case "uitv.140.201906.ext":
                $base = self::getSchema("uitv.120.201701.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'segmentCode' => 1,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.120.202002.ext":
            case "uitv.140.202002.ext":
                $base = self::getSchema("uitv.120.201906.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'accuCapaciteit' => 4,
                    'actieradius' => 4,
                    'verbruikKWh' => 4,
                    'externOpladen' => 1,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.120.202109.ext":
            case "uitv.140.202109.ext":
                $base = self::getSchema("uitv.120.202002.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'systeemvermogen' => 4,
                    'vermogenElektromotor1' => 4,
                    'vermogenElektromotor2' => 4,
                    'maxLaadsnelheidAC' => 4,
                    'maxLaadsnelheidDC' => 4,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.120.202311.ext":
            case "uitv.140.202311.ext":
                $base = self::getSchema("uitv.120.202109.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'minLaadtijdUurAC' => 3,
                    'minLaadtijdMinutenAC' => 3,
                    'minLaadtijdVanAC' => 3,
                    'minLaadtijdTotAC' => 3,
                    'minLaadtijdUurDC' => 3,
                    'minLaadtijdMinutenDC' => 3,
                    'minLaadtijdVanDC' => 3,
                    'minLaadtijdTotDC' => 3,
                    'actieradiusPraktijk' => 4,
                    'zonneAuto' => 1,
                    'laadVolume' => 5,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.150":
                return [
                    'uitvoeringNummer' => 6,
                    'uitvoering' => 30,
                    'aantalCilinders' => 2,
                    'takt' => 2,
                    'motorinhoud' => 4,
                    'motorvermogen' => 3,
                    'motorvermogenTpm' => 5,
                    'gewicht' => 5,
                    'versnellingCode' => 2,
                    'import' => 1,
                    'modelNummer' => 4,
                    'mutatiecode' => 1,
                ];
            case "uitv.150.ext":
                $base = self::getSchema("uitv.150");
                unset($base['mutatiecode']);
                $fields = [
                    'topsnelheid' => 3,
                    'acceleratie' => 3,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.150.200807.ext":
                $base = self::getSchema("uitv.150.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'geleverdVan' => 8,
                    'geleverdTot' => 8,
                    'carrosserieGeleverdVan' => 8,
                    'carrosserieGeleverdTot' => 8,
                    'uitvoeringLang' => 50,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.150.201209.ext":
                $base = self::getSchema("uitv.150.200807.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'brandstof' => 1,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.150.202002.ext":
                $base = self::getSchema("uitv.150.201209.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'accuCapaciteit' => 4,
                    'actieradius' => 4,
                    'verbruikKWh' => 4,
                    'externOpladen' => 1,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.150.202109.ext":
                $base = self::getSchema("uitv.150.202002.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'maxLaadsnelheidAC' => 4,
                    'maxLaadsnelheidDC' => 4,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.150.202311.ext":
                $base = self::getSchema("uitv.150.202109.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'minLaadtijdUurAC' => 3,
                    'minLaadtijdMinutenAC' => 3,
                    'minLaadtijdVanAC' => 3,
                    'minLaadtijdTotAC' => 3,
                    'minLaadtijdUurDC' => 3,
                    'minLaadtijdMinutenDC' => 3,
                    'minLaadtijdVanDC' => 3,
                    'minLaadtijdTotDC' => 3,
                    'actieradiusPraktijk' => 4,
                    'nakedBike' => 1,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.180.201209.ext":
                $base = self::getSchema("uitv.180.200807.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'aantalPersonen' => 2,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.180.201404.ext":
                $base = self::getSchema("uitv.180.201209.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'segmentCode' => 1,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.180.202002.ext":
                $base = self::getSchema("uitv.180.201404.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'accuCapaciteit' => 4,
                    'actieradius' => 4,
                    'verbruikKWh' => 4,
                    'externOpladen' => 1,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.180.202109.ext":
                $base = self::getSchema("uitv.180.202002.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'systeemvermogen' => 4,
                    'vermogenElektromotor1' => 4,
                    'vermogenElektromotor2' => 4,
                    'maxLaadsnelheidAC' => 4,
                    'maxLaadsnelheidDC' => 4,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            case "uitv.180.202311.ext":
                $base = self::getSchema("uitv.180.202109.ext");
                unset($base['mutatiecode']);
                $fields = [
                    'minLaadtijdUurAC' => 3,
                    'minLaadtijdMinutenAC' => 3,
                    'minLaadtijdVanAC' => 3,
                    'minLaadtijdTotAC' => 3,
                    'minLaadtijdUurDC' => 3,
                    'minLaadtijdMinutenDC' => 3,
                    'minLaadtijdVanDC' => 3,
                    'minLaadtijdTotDC' => 3,
                    'actieradiusPraktijk' => 4,
                    'zonneAuto' => 1,
                    'mutatiecode' => 1,
                ];
                return array_merge($base, $fields);
            default:
                return new Error(messages: ["No fields specified for file $filename"]);

        }
    }
}
