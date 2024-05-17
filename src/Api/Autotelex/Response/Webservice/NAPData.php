<?php

namespace AtpCore\Api\Autotelex\Response\Webservice;

class NAPData
{
    /** @var integer|null */
    public $_betrouwbaarheidcode;
    /** @var string */
    public $_betrouwbaarheidsomschrijving;
    /** @var integer|null */
    public $_betrouwbaarheidtoelichtingcode;
    /** @var string|null */
    public $_betrouwbaarheidtoelichtingomschrijving;
    /** @var boolean */
    public $_callSucces;
    /** @var boolean */
    public $_callSuccessBetrouwbaarheid;
    /** @var boolean */
    public $_callSuccessOverzicht;
    /** @var boolean */
    public $_callViaRdc;
    /** @var string|null */
    public $_created;
    /** @var string|null */
    public $_errorMessage;
    /** @var string|null */
    public $_errorMessageBetrouwbaarheid;
    /** @var string|null */
    public $_errorMessageOverzicht;
    /** @var integer|null */
    public $_errorMessageOverzichtCode;
    /** @var \stdClass */
    public $_kilometerstanden;
    /** @var string|null */
    public $_tellersoort;
    /** @var integer|null */
    public $_tellerstand;
    /** @var \stdClass */
    public $_tellerstandoverzicht;
    /** @var string|null */
    public $_toelichting;
    /** @var boolean */
    public $_trendbreukAanwezig;
    /** @var boolean */
    public $_trendbreukOpgetreden;
    /** @var string|null */
    public $_x003C_Kenteken_x003E_k__BackingField;
    /** @var integer */
    public $_x003C_Kilometerstand_x003E_k__BackingField;
    /** @var string|null */
    public $_x003C_Oordeel_x003E_k__BackingField;
    /** @var string|null */
    public $_x003C_Rijbewijsnummer_x003E_k__BackingField;
}