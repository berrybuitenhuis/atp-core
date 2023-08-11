<?php

namespace AtpCore\Api\Autotelex\Response;

class VehicleVariables
{
    /** @var RegisterData|null */
    public $aanmeldData;
    /** @var integer|null */
    public $aantalSleutels;
    /** @var Options */
    public $accessoires;
    /** @var boolean|null */
    public $aircoAanwezig;
    /** @var boolean|null */
    public $aircoWerkt;
    /** @var integer|null */
    public $autotelexUitvoeringID;
    /** @var TiresData */
    public $bandenGegevens;
    /** @var string|null */
    public $bekleding;
    /** @var Destination */
    public $bestemming;
    /** @var integer */
    public $biedingID;
    /** @var BidData */
    public $biedingen;
    /** @var ChargingCableType|null  */
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
    public $datumDistributieriemVervangen;
    /** @var string|null */
    public $datumRoetfilterVervangen;
    /** @var string */
    public $datumVerwacht;
    /** @var boolean|null */
    public $distributieriemVervangen;
    /** @var \stdClass */
    public $dmsData;
    /** @var boolean|null */
    public $driveable;
    /** @var integer */
    public $duplicaatcode;
    /** @var integer */
    public $externalId;
    /** @var Files|null */
    public $files;
    /** @var string|null */
    public $gebruiktAls;
	/** @var HeaderCalculation */
    public $headerberekening;
    /** @var TradeData */
    public $inruilenOpVoertuig;
    /** @var boolean|null */
    public $instructieboekjesAanwezig;
    /** @var string|null */
    public $interieurBekleding;
    /** @var string|null */
    public $interieurKleur;
    /** @var integer|null */
    public $inruilBod;
    /** @var boolean|null */
    public $isAdvertisable;
    /** @var boolean */
    public $isBtwVoertuigBlocked;
    /** @var string|null */
    public $kleur;
    /** @var integer|null */
    public $kmstandDistributieriemVervangen;
    /** @var string|null */
    public $laksoort;
    /** @var string|null */
    public $landVanHerkomst;
    /** @var \stdClass */
    public $loadAddress;
    /** @var boolean|null */
    public $motorManagementLampjeAan;
    /** @var boolean|null */
    public $myPurchasePriceRequired;
    /** @var string */
    public $onderhoudsboekjes;
    /** @var string */
    public $opmerking;
    /** @var string|null */
    public $opmerkingen;
    /** @var Options */
    public $opties;
	/** @var Owner */
    public $owner;
    /** @var Packets */
	public $pakketten;
    /** @var integer|null */
    public $physicalOrOnline;
    /** @var integer|null */
    public $registeredInCountry;
	/** @var ReportUrl */
    public $reportURLs;
    /** @var Requester */
    public $requester;
    /** @var boolean|null */
    public $roetfilterVervangen;
    /** @var boolean|null */
    public $rookvrij;
    /** @var DamageData */
    public $schadeGegevens;
    /** @var string|null */
    public $staatMotor;
    /** @var Options */
	public $standaardOpties;
    /** @var boolean|null */
    public $storingsmeldingVrij;
    /** @var integer */
    public $tellerstandVerwacht;
    /** @var boolean|null */
    public $testDriven;
    /** @var string|null */
    public $tmcVehicleStatus;
    /** @var string|null */
    public $tweedeKleur;
    /** @var integer|null */
    public $typeOfPurchase;
    /** @var boolean|null */
    public $tradeInPriceApproved;
    /** @var boolean */
    public $trekhaakAanwezig;
    /** @var boolean|null */
    public $urgentAppraisal;
    /** @var string */
    public $vehicleChargingCable;
    /** @var boolean|null */
    public $verhuisgoed;
    /** @var string */
    public $winterbanden;
    /** @var boolean|null */
    public $isBtwVoertuig;
    /** @var string */
    public $kenteken;
    /** @var integer */
	public $kilometerstand;
}