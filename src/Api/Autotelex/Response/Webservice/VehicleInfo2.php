<?php

namespace AtpCore\Api\Autotelex\Response\Webservice;

class VehicleInfo2
{
    /** @var \stdClass */
    public $advertentiePortalURLs;
    /** @var Behavior */
    public $behavior;
    /** @var string|null */
    public $datumTaxatie;
    /** @var UserData */
    public $gebruikerGegevens;
    /** @var NAPData */
    public $napGegevens;
    /** @var RDWData|null */
    public $rdwGegevens;
    /** @var integer|null */
    public $restWaardenMeldingId;
    /** @var ResidualValues */
    public $restwaarden;
    /** @var VehicleVariables */
	public $voertuigVariabelen;
}