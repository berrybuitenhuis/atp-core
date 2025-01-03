<?php

namespace AtpCore\Api\Autotelex\Response\Webservice;

class VehicleInfo
{
    /** @var string|null */
    public $aandrijving;
    /** @var integer */
    public $aantalDeuren;
    /** @var integer */
    public $aantalZitplaatsen;
    /** @var string */
    public $acceleratie;
    /** @var Options|null */
    public $accessoires;
    /** @var integer|null */
    public $accuCapaciteit;
    /** @var integer|null */
    public $actieradius;
    /** @var string|null */
    public $advertisementUrl;
    /** @var integer|null */
    public $afleverkosten;
    /** @var string|null */
    public $apkVervaldatum;
    /** @var integer */
    public $autotelexKMStand;
    /** @var string|null */
    public $brandstof;
    /** @var integer|null */
    public $cargoVolume;
    /** @var integer */
    public $co2;
    /** @var integer */
    public $cilinderinhoud;
    /** @var integer */
    public $cylinders;
    /** @var integer */
    public $destinationTypeId;
    /** @var string|null */
    public $eersteAfgifteNL;
    /** @var string|null */
    public $eersteToelating;
    /** @var integer|null */
    public $electricEngine1Power;
    /** @var integer|null */
    public $electricEngine2Power;
    /** @var string|null */
    public $energielabel;
    /** @var string|null */
    public $euroklasse;
    /** @var integer */
    public $externalID;
    /** @var integer */
    public $fijnstof;
    /** @var integer */
    public $gvw;
    /** @var string|null */
    public $geleverdTot;
    /** @var string|null */
    public $geleverdVan;
    /** @var integer|null */
    public $geschiktVoorExportMeldingID;
    /** @var integer|null */
    public $gewicht;
    /** @var string */
    public $handelswaarde;
    /** @var string|null */
    public $importDate;
    /** @var string|null */
    public $importPurchaseValueDeviation;
    /** @var boolean|null */
    public $importPurchaseValueDeviationShowAlert;
    /** @var integer|null */
    public $importPurchaseValueLicensePlate;
    /** @var integer|null */
    public $importPurchaseValueTaxRecord;
    /** @var string */
    public $internetwaarde;
    /** @var boolean */
    public $isGeschiktVoorExport;
    /** @var boolean */
    public $isImport;
    /** @var integer */
    public $kmStand;
    /** @var string */
    public $kenteken;
    /** @var string|null */
    public $kleur;
    /** @var string|null */
    public $koetswerk;
    /** @var integer */
    public $koppel;
    /** @var integer|null */
    public $laadruimteHoogte;
    /** @var integer|null */
    public $laadruimteLengte;
    /** @var integer */
    public $laadvermogen;
    /** @var string|null */
    public $laatsteTenaamstelling;
    /** @var MMT */
    public $mmt;
    /** @var integer */
    public $margeOfBtw;
    /** @var string|null */
    public $maxChargingSpeedAC;
    /** @var string|null */
    public $maxChargingSpeedDC;
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
    /** @var boolean|null */
    public $nakedBike;
    /** @var string */
    public $nieuwPrijs;
    /** @var string */
    public $nieuwPrijsInclOpties;
    /** @var string */
    public $nieuwPrijsMelding;
    /** @var integer|null */
    public $nieuwPrijsMeldingID;
    /** @var integer|null */
    public $numberOfGears;
    /** @var string */
    public $opmerkingen;
    /** @var Options|null */
    public $opties;
    /** @var Packets|null */
    public $pakketten;
    /** @var boolean */
    public $rdwData;
    /** @var RDWVehicleData|null */
    public $rdwVoertuigData;
    /** @var boolean */
    public $rechargePossibility;
    /** @var integer|null */
    public $roetfilterVerwijdertStatus;
    /** @var boolean */
    public $roetfilter;
    /** @var integer|null */
    public $roetFilterVerwijdertStatus;
    /** @var string|null */
    public $segmentCode;
    /** @var string|null */
    public $segmentDescription;
    /** @var Options|null */
    public $standaardOpties;
    /** @var boolean */
    public $stateIsFrozen;
    /** @var boolean */
    public $statusAfgehandeld;
    /** @var boolean */
    public $statusBestemd;
    /** @var boolean */
    public $statusIngekocht;
    /** @var boolean */
    public $statusNietIngekocht;
    /** @var boolean */
    public $statusNieuw;
    /** @var integer|null */
    public $systemOutput;
    /** @var string|null */
    public $takt;
    /** @var integer|null */
    public $tankInhoud;
    /** @var boolean */
    public $taxatieData;
    /** @var boolean|null */
    public $toonRoetFilterWaarschuwingsBericht;
    /** @var integer */
    public $topsnelheid;
    /** @var string|null */
    public $transmissie;
    /** @var integer */
    public $trekGewichtGeremd;
    /** @var integer */
    public $trekGewichtOngeremd;
    /** @var boolean */
    public $turbo;
    /** @var integer|null */
    public $uitvoeringID;
    /** @var boolean */
    public $unlinkedLicenseplate;
    /** @var string|null */
    public $vehicleExteriorColor;
    /** @var string|null */
    public $vehicleExteriorColorCode;
    /** @var string|null */
    public $vehicleInteriorColor;
    /** @var string|null */
    public $vehicleInteriorColorCode;
    /** @var string|null */
    public $vehicleRoofColor;
    /** @var string */
    public $verbruik;
    /** @var string */
    public $verbruikBinnen;
    /** @var string */
    public $verbruikBuiten;
    /** @var string|null */
    public $verbruikKwh;
    /** @var string */
    public $verkoopwaarde;
    /** @var integer */
    public $vermogen;
    /** @var integer */
    public $version;
    /** @var boolean */
    public $voertuigData;
    /** @var string */
    public $voertuigSoort;
    /** @var string */
    public $wegenbelasting;
    /** @var string */
    public $wegenbelastingMelding;
    /** @var integer */
    public $wegenbelastingMeldingID;
    /** @var integer */
    public $wielbasis;
}