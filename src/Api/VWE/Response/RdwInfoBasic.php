<?php

namespace AtpCore\Api\VWE\Response;

class RdwInfoBasic
{
    /** @var string */
    public $kenteken;
    /** @var boolean */
    public $isMeldCodeCorrect;
    /** @var string|null */
    public $kentekenSignaal;
    /** @var string */
    public $merk;
    /** @var string */
    public $merk_code;
    /** @var string */
    public $handelsbenaming;
    /** @var string */
    public $voertuigsoort;
    /** @var string */
    public $brandstof1;
    /** @var string */
    public $brandstof1_code;
    /** @var string|null*/
    public $brandstof2;
    /** @var string|null */
    public $brandstof2_code;
    /** @var string */
    public $kleur1;
    /** @var mixed */
    public $kleur1_code;
    /** @var string|null*/
    public $kleur2;
    /** @var mixed */
    public $kleur2_code;
    /** @var int */
    public $aantalZitplaatsen;
    /** @var int|null */
    public $aantalStaanplaatsen;
    /** @var string */
    public $datumEersteToelatingInternationaal;
    /** @var string */
    public $datumEersteToelatingNationaal;
    /** @var string */
    public $datumAansprakelijkheid;
    /** @var string */
    public $datumVervalApk;
    /** @var int */
    public $aantalCilinders;
    /** @var int */
    public $cilinderinhoud;
    /** @var int */
    public $massaLeegVoertuig;
    /** @var int */
    public $laadvermogen;
    /** @var int */
    public $maximumMassa;
    /** @var int */
    public $massaRijklaar;
    /** @var int */
    public $maximumMassaOngeremd;
    /** @var int */
    public $maximumMassaGeremd;
    /** @var int|null */
    public $maximumMassaOpleggerGeremd;
    /** @var int|null */
    public $maximumMassaAutonoomGeremd;
    /** @var int */
    public $maximumMassaMiddenasGeremd;
    /** @var string */
    public $tijdAansprakelijkheid;
    /** @var string|null */
    public $brandstof3;
}