<?php

namespace AtpCore\Api\Autotelex\Response;

class VehicleVariables
{
    /** @var integer */
    public $aantalSleutels;
    /** @var \stdClass */
    public $accessoires;
    /** @var boolean */
    public $aircoAanwezig;
    /** @var boolean */
    public $aircoWerkt;
    /** @var integer */
    public $autotelexUitvoeringID;
    /** @var TiresData */
    public $bandenGegevens;
    /** @var string */
    public $bekleding;
    /** @var Destination */
    public $bestemming;
    /** @var integer */
    public $biedingID;
    /** @var BidData */
    public $biedingen;
    /** @var string */
    public $chassisnummer;
    /** @var string */
    public $conditieExterieur;
    /** @var string */
    public $conditieInterieur;
    /** @var string */
    public $created;
    /** @var string */
    public $datumDistributieriemVervangen;
    /** @var string */
    public $datumVerwacht;
    /** @var boolean */
    public $distributieriemVervangen;
    /** @var \stdClass */
    public $dmsData;
    /** @var boolean */
    public $driveable;
    /** @var integer */
    public $duplicaatcode;
    /** @var integer */
    public $externalId;
    /** @var Files */
    public $files;
    /** @var string */
    public $gebruiktAls;
	/** @var HeaderCalculation */
    public $headerberekening;
    /** @var TradeData */
    public $inruilenOpVoertuig;
    /** @var boolean */
    public $instructieboekjesAanwezig;
    /** @var string */
    public $interieurBekleding;
    /** @var string */
    public $interieurkleur;
    /** @var boolean */
    public $isAdvertisable;
    /** @var boolean */
    public $isBtwVoertuigBlocked;
    /** @var string */
    public $kleur;
    /** @var integer */
    public $kmstandDistributieriemVervangen;
    /** @var string */
    public $laksoort;
    /** @var \stdClass */
    public $loadAddress;
    /** @var boolean */
    public $motorManagementLampjeAan;
    /** @var string */
    public $onderhoudsboekjes;
    /** @var string */
    public $opmerking;
    /** @var Options */
    public $opties;
	/** @var Owner */
    public $owner;
    /** @var Packets */
	public $pakketten;
    /** @var integer */
    public $physicalOrOnline;
	/** @var ReportUrl */
    public $reportURLs;
    /** @var Requester */
    public $requester;
    /** @var boolean */
    public $rookvrij;
    /** @var DamageData */
    public $schadeGegevens;
    /** @var string */
    public $staatMotor;
    /** @var Options */
	public $standaardOpties;
    /** @var boolean */
    public $storingsmeldingVrij;
    /** @var integer */
    public $tellerstandVerwacht;
    /** @var boolean */
    public $testDriven;
    /** @var boolean */
    public $trekhaakAanwezig;
    /** @var boolean */
    public $urgentAppraisal;
    /** @var string */
    public $vehicleChargingCable;
    /** @var string */
    public $winterbanden;
    /** @var boolean */
    public $isBtwVoertuig;
    /** @var string */
    public $kenteken;
    /** @var integer */
	public $kilometerstand;
}