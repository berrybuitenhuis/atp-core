<?php

namespace AtpCore\Api\Autotelex\Response;

class VehicleVariables
{
public $aantalSleutels;
    public $accessoires;
    public $aircoAanwezig;
    public $aircoWerkt;
    public $autotelexUitvoeringID;
    /** @var TiresData */
    public $bandenGegevens;
    public $bekleding;
    /** @var Destination */
    public $bestemming;
    public $biedingID;
    public $biedingen;
    public $chassisnummer;
    public $conditieExterieur;
    public $conditieInterieur;
    public $created;
    public $datumDistributieriemVervangen;
    public $datumVerwacht;
    public $distributieriemVervangen;
    public $dmsData;
    public $driveable;
    public $duplicaatcode;
    public $externalId;
    /** @var Files */
    public $files;
    public $gebruiktAls;
	/** @var HeaderCalculation */
    public $headerberekening;
    /** @var TradeData */
    public $inruilenOpVoertuig;
	public $instructieboekjesAanwezig;
    public $interieurBekleding;
    public $interieurkleur;
    public $isAdvertisable;
    public $isBtwVoertuigBlocked;
    public $kleur;
    public $kmstandDistributieriemVervangen;
    public $laksoort;
    public $loadAddress;
    public $motorManagementLampjeAan;
    public $onderhoudsboekjes;
    public $opmerking;
    /** @var Options */
    public $opties;
	/** @var Owner */
    public $owner;
    /** @var Packets */
	public $pakketten;
    public $physicalOrOnline;
	/** @var ReportUrl */
    public $reportURLs;
    /** @var Requester */
    public $requester;
    public $rookvrij;
    /** @var DamageData */
    public $schadeGegevens;
	public $staatMotor;
    /** @var Options */
	public $standaardOpties;
    public $storingsmeldingVrij;
	public $tellerstandVerwacht;
	public $testDriven;
	public $trekhaakAanwezig;
	public $urgentAppraisal;
	public $vehicleChargingCable;
	public $winterbanden;
	public $isBtwVoertuig;
	public $kenteken;
	public $kilometerstand;
}