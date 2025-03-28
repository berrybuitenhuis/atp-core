<?php

namespace AtpCore\Api\Autotelex\Response;

class VehicleVariables
{
    /** @var RequestData|null */
    public $aanmeldData;
    /** @var integer|null */
    public $aantalSleutels;
    /** @var integer|null */
    public $aantalZenders;
    /** @var Option[]|null */
    public $accessoires;
    /** @var string|null */
    public $afhandelingsMethode;
    /** @var boolean|null */
    public $aircoAanwezig;
    /** @var boolean|null */
    public $aircoWerkt;
    /** @var integer */
    public $autotelexUitvoeringId;
    /** @var TireData[]|null */
    public $bandenGegevens;
    /** @var boolean|null */
    public $batteryChecked;
    /** @var integer|null */
    public $batteryHealth;
    /** @var integer|null */
    public $bedrijfId;
    /** @var string|null */
    public $bekleding;
    /** @var Destination */
    public $bestemming;
    /** @var integer */
    public $bieding;
    /** @var integer */
    public $biedingID;
    /** @var Bid[] */
    public $biedingen;
    /** @var integer|null */
    public $bodytypeId;
    /** @var integer|null */
    public $co2;
    /** @var ChargingCableType[]|null */
    public $chargingCableTypes;
    /** @var boolean|null */
    public $chargingCablesPresent;
    /** @var string */
    public $chassisnummer;
    /** @var string */
    public $conditieExterieur;
    /** @var string */
    public $conditieInterieur;
    /** @var integer|null */
    public $consumerInspectionStatus;
    /** @var string */
    public $created;
    /** @var string|null */
    public $customerToken;
    /** @var string|null */
    public $datumDistributieriemVervangen;
    /** @var string|null */
    public $datumEersteToelating;
    /** @var string|null */
    public $datumRegistratieImport;
    /** @var string|null */
    public $datumRoetfilterVervangen;
    /** @var string */
    public $datumVerwacht;
    /** @var boolean|null */
    public $distributieriemVervangen;
    /** @var DmsData */
    public $dmsData;
    /** @var integer|null */
    public $doors;
    /** @var boolean|null */
    public $driveable;
    /** @var integer|null */
    public $drivetrainId;
    /** @var integer */
    public $duplicaatCode;
    /** @var integer */
    public $externalId;
    /** @var string|null */
    public $externalParameters;
    /** @var File[]|null */
    public $files;
    /** @var integer|null */
    public $fueltypeId;
    /** @var string|null */
    public $gebruiktAls;
    /** @var Grading|null */
    public $grading;
    /** @var HeaderCalculation */
    public $headerberekening;
    /** @var boolean */
    public $highestBidder;
    /** @var string|null */
    public $inlogTicket;
    /** @var integer */
    public $inruilBod;
    /** @var TradeInOnVehicle */
    public $inruilenOpVoertuig;
    /** @var boolean|null */
    public $instructieboekjesAanwezig;
    /** @var string|null */
    public $interieurBekleding;
    /** @var string|null */
    public $interieurKleur;
    /** @var boolean */
    public $isAdvertisable;
    /** @var boolean */
    public $isBtwVoertuigBlocked;
    /** @var null */
    public $jatoVehiclePricelist;
    /** @var string|null */
    public $jatoVehicleTypeId;
    /** @var string|null */
    public $klantGegevens;
    /** @var string|null */
    public $kleur;
    /** @var string|null */
    public $kleurtint;
    /** @var integer|null */
    public $kmstandDistributieriemVervangen;
    /** @var integer|null */
    public $kmstandRoetfilterVervangen;
    /** @var string|null */
    public $laksoort;
    /** @var string|null */
    public $landVanHerkomst;
    /** @var LoadAddress|null */
    public $loadAddress;
    /** @var string|null */
    public $make;
    /** @var integer|null */
    public $meldcode;
    /** @var string|null */
    public $model;
    /** @var boolean|null */
    public $motorManagementLampjeAan;
    /** @var boolean */
    public $myPurchasePriceRequired;
    /** @var string */
    public $onderhoudsboekjes;
    /** @var string */
    public $opmerking;
    /** @var string|null */
    public $opmerkingen;
    /** @var integer|null */
    public $optiebedrag;
    /** @var Option[]|null */
    public $opties;
    /** @var Owner */
    public $owner;
    /** @var OptionPackage[]|null */
    public $pakketten;
    /** @var integer|null */
    public $particulateFilterStatus;
    /** @var integer|null */
    public $physicalOrOnline;
    /** @var integer|null */
    public $powerKw;
    /** @var integer */
    public $registeredInCountry;
    /** @var string */
    public $registeredInCountryIso3166;
    /** @var string|null */
    public $reparatie;
    /** @var ReportUrl[] */
    public $reportUrls;
    /** @var Requester */
    public $requester;
    /** @var string|null */
    public $rijbewijsnummer;
    /** @var boolean|null */
    public $roetfilterVervangen;
    /** @var boolean|null */
    public $rookvrij;
    /** @var DamageData */
    public $schadeGegevens;
    /** @var integer|null */
    public $seats;
    /** @var string|null */
    public $staatMotor;
    /** @var Option[]|null */
    public $standaardOpties;
    /** @var boolean|null */
    public $storingsmeldingVrij;
    /** @var string|null */
    public $tco;
    /** @var string */
    public $tmcVehicleStatus;
    /** @var integer */
    public $tellerstandVerwacht;
    /** @var boolean|null */
    public $testDriven;
    /** @var boolean|null */
    public $tradeInPriceApproved;
    /** @var string|null */
    public $transmissionAutomatic;
    /** @var boolean */
    public $trekhaakAanwezig;
    /** @var string|null */
    public $tweedeKleur;
    /** @var integer|null */
    public $typeOfPurchase;
    /** @var boolean|null */
    public $urgentAppraisal;
    /** @var string|null */
    public $variant;
    /** @var integer */
    public $vehicleChargingCable;
    /** @var integer|null */
    public $vehicleIds;
    /** @var boolean */
    public $vehicleReadySignalSentToBuyer;
    /** @var boolean|null */
    public $verhuisgoed;
    /** @var string|null */
    public $voertuigSoortId;
    /** @var boolean|null */
    public $vraagBiedingen;
    /** @var integer|null */
    public $wheelbaseCm;
    /** @var string */
    public $winterbanden;
    /** @var boolean */
    public $isBtwVoertuig;
    /** @var string */
    public $kenteken;
    /** @var integer */
    public $kilometerstand;
    /** @var boolean|null */
    public $rdwUpdate;
    /** @var boolean|null */
    public $restwaardenUpdate;
    /** @var boolean|null */
    public $tellerstandControlerenUpdate;
    /** @var integer */
    public $version;
}