<?php

namespace AtpCore\Api\Autotelex\OpenDB;

class Uitvoering
{
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
}
