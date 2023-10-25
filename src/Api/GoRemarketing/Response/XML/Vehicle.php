<?php

namespace AtpCore\Api\GoRemarketing\Response\XML;

class Vehicle
{
    /** @var string */
    public $actie;
    /** @var string */
    public $ucc_auto_id;
    /** @var string */
    public $source;
    /** @var integer */
    public $bedrijf_id;
    /** @var string */
    public $bedrijfsnaam;
    /** @var string */
    public $contactpersoon;
    /** @var string */
    public $straat;
    /** @var integer */
    public $huisnr;
    /** @var string */
    public $postcode;
    /** @var string */
    public $plaats;
    /** @var string */
    public $email;
    /** @var string */
    public $nr_telefoon;
    /** @var string */
    public $kenteken;
    /** @var string */
    public $merk;
    /** @var string */
    public $type;
    /** @var string */
    public $model;
    /** @var string */
    public $modelvan;
    /** @var string */
    public $modeltot;
    /** @var string */
    public $inrichting;
    /** @var integer */
    public $deuren;
    /** @var string */
    public $transmissie;
    /** @var string */
    public $brandstof;
    /** @var integer */
    public $bouwjaar;
    /** @var integer */
    public $tellerstand;
    /** @var string */
    public $tellersoort;
    /** @var integer|null */
    public $afleesdatumtellerstand;
    /** @var string|null */
    public $vin;
    /** @var integer|null */
    public $vermogen;
    /** @var string */
    public $vermogensoort;
    /** @var integer|null */
    public $versnellingen;
    /** @var integer */
    public $cilinderinhoud;
    /** @var integer */
    public $aantalcylinders;
    /** @var integer */
    public $gewicht;
    /** @var integer */
    public $wegenbelastingmin;
    /** @var integer */
    public $wegenbelastingmax;
    /** @var string|null */
    public $kleur;
    /** @var integer */
    public $metallic;
    /** @var string */
    public $basiskleur;
    /** @var string|null */
    public $interieurkleur;
    /** @var string */
    public $interieur;
    /** @var string */
    public $btwmarge;
    /** @var integer */
    public $showroomvraagprijs;
    /** @var integer */
    public $rijklaarmaakkosten;
    /** @var integer */
    public $nieuwprijs;
    /** @var integer */
    public $bpm;
    /** @var integer */
    public $consumentenprijs;
    /** @var integer */
    public $btwwaarde;
    /** @var integer */
    public $nettocatalogusprijs;
    /** @var integer */
    public $teruggerekendecatalogusprijs;
    /** @var integer */
    public $teruggerekendebtwnieuwprijs;
    /** @var integer */
    public $teruggerekendenettocatalogusprijs;
    /** @var integer */
    public $bpm_cat;
    /** @var string */
    public $invoerdatum;
    /** @var string */
    public $binnenkomstdatum;
    /** @var string */
    public $inkoopdatum;
    /** @var string */
    public $land;
    /** @var string */
    public $lokatie;
    /** @var string */
    public $eerste_toelating;
    /** @var string */
    public $datumdeel1;
    /** @var string|null */
    public $datumdeel2;
    /** @var string|null */
    public $emissieklasse;
    /** @var string|null */
    public $verbruik;
    /** @var string|null */
    public $verbruik_stad;
    /** @var string|null */
    public $verbruik_snelweg;
    /** @var integer */
    public $trekgewicht_geremd;
    /** @var integer */
    public $trekgewicht_ongeremd;
    /** @var integer */
    public $sleutels;
    /** @var string|null */
    public $energielabel;
    /** @var integer|null */
    public $co2_uitstoot;
    /** @var integer|null */
    public $roetdeeltjes;
    /** @var string|null */
    public $bijtellingspercentage;
    /** @var string */
    public $soort;
    /** @var Additional */
    public $additional;
    /** @var string[]|null */
    public $fotos;
    /** @var Accessories|null */
    public $accessoires;
}