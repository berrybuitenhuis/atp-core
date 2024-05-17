<?php

namespace AtpCore\Api\Autotelex\Response;

class BasicData
{
    /** @var string|null */
    public $aandrijving;
    /** @var integer */
    public $aantalDeuren;
    /** @var integer */
    public $aantalZitplaatsen;
    /** @var double */
    public $acceleratie;
    /** @var Option[]|null */
    public $accessoires;
    /** @var integer|null */
    public $accucapaciteit;
    /** @var integer|null */
    public $actieradius;
    /** @var integer|null */
    public $afleverkosten;
    /** @var integer */
    public $autotelexKmStand;
    /** @var integer|null */
    public $bouwjaar;
    /** @var integer|null */
    public $bouwmaand;
    /** @var string */
    public $brandstof;
    /** @var integer */
    public $co2;
    /** @var integer|null */
    public $cargoVolume;
    /** @var integer */
    public $cilinderinhoud;
    /** @var integer */
    public $cilinders;
    /** @var integer|null */
    public $electricEngine1Power;
    /** @var integer|null */
    public $electricEngine2Power;
    /** @var string|null */
    public $energielabel;
    /** @var string|null */
    public $euroklasse;
    /** @var integer */
    public $fijnstof;
    /** @var integer */
    public $gvw;
    /** @var string|null */
    public $geleverdTot;
    /** @var string|null */
    public $geleverdVan;
    /** @var integer */
    public $gewicht;
    /** @var string|null */
    public $importDate;
    /** @var double|null */
    public $importPurchaseValueDeviation;
    /** @var boolean|null */
    public $importPurchaseValueDeviationShowAlert;
    /** @var integer|null */
    public $importPurchaseValueLicensePlate;
    /** @var integer|null */
    public $importPurchaseValueTaxRecord;
    /** @var boolean */
    public $isGeel;
    /** @var boolean */
    public $isGrijs;
    /** @var string */
    public $kenteken;
    /** @var string */
    public $koetswerk;
    /** @var integer */
    public $koppel;
    /** @var MMT */
    public $mmt;
    /** @var double|null */
    public $maxChargingSpeedAC;
    /** @var double|null */
    public $maxChargingSpeedDC;
    /** @var integer|null */
    public $maximaleConstructieSnelheidBrommer;
    /** @var integer|null */
    public $minChargingTimeFromAC;
    /** @var integer|null */
    public $minChargingTimeFromDC;
    /** @var integer|null */
    public $minChargingTimeHoursAC;
    /** @var integer|null */
    public $minChargingTimeHoursDC;
    /** @var integer|null */
    public $minChargingTimeMinutesAC;
    /** @var integer|null */
    public $minChargingTimeMinutesDC;
    /** @var integer|null */
    public $minChargingTimeToAC;
    /** @var integer|null */
    public $minChargingTimeToDC;
    /** @var string|null */
    public $modelVariantGeleverdTot;
    /** @var string|null */
    public $modelVariantGeleverdVan;
    /** @var integer */
    public $nieuwPrijs;
    /** @var integer|null */
    public $numberOfGears;
    /** @var Option[]|null */
    public $opties;
    /** @var OptionPackage[]|null */
    public $pakketten;
    /** @var boolean */
    public $rechargePossibility;
    /** @var boolean */
    public $roetfilter;
    /** @var string|null */
    public $segment;
    /** @var string|null */
    public $segmentDescription;
    /** @var integer|null */
    public $segmentId;
    /** @var Option[]|null */
    public $standaardOpties;
    /** @var Status */
    public $status;
    /** @var integer|null */
    public $systemOutput;
    /** @var string|null */
    public $takt;
    /** @var integer */
    public $topsnelheid;
    /** @var string|null */
    public $transmissie;
    /** @var boolean */
    public $turbo;
    /** @var integer */
    public $uitvoeringID;
    /** @var string|null */
    public $vehicleExteriorColor;
    /** @var string|null */
    public $vehicleExteriorColorCode;
    /** @var double */
    public $verbruik;
    /** @var double */
    public $verbruikBinnen;
    /** @var double */
    public $verbruikBuiten;
    /** @var double|null */
    public $verbruikKwh;
    /** @var integer */
    public $vermogen;
    /** @var string */
    public $voertuigSoort;
    /** @var string */
    public $wegenbelasting;
    /** @var integer */
    public $wielbasis;
}