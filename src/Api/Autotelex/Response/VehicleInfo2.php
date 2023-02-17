<?php

namespace AtpCore\Api\Autotelex\Response;

class VehicleInfo2
{
    /** @var object|null */
    public $advertentiePortalURLs;
    /** @var Behavior */
    public $behavior;
    /** @var string */
    public $datumTaxatie;
    /** @var UserData */
    public $gebruikerGegevens;
    /** @var NAPData */
    public $napGegevens;
    /** @var RDWData */
    public $rdwGegevens;
    /** @var ResidualValues */
    public $restwaarden;
    /** @var VehicleVariables */
	public $voertuigVariabelen;
}