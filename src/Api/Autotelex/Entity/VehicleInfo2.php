<?php

namespace AtpCore\Api\Autotelex\Entity;

class VehicleInfo2
{
    public $advertentiePortalURLs;
    /** @var Behavior */
    public $behavior;
    public $datumTaxatie;
    /** @var UserData */
    public $gebruikerGegevens;
    /** @var NAPData */
    public $napGegevens;
    public $rdwGegevens;
    /** @var ResidualValues */
    public $restwaarden;
    /** @var VehicleVariables */
	public $voertuigVariabelen;
}