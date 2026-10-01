<?php

namespace AtpCore\Api\GoRemarketing\Response;

class Car
{
    /** @var string */
    public $id;
    /** @var string */
    public $kenteken;
    /** @var integer */
    public $id_medewerker;
    /** @var integer */
    public $id_verkochtdoor;
    /** @var integer */
    public $id_klant_van;
    /** @var integer|null */
    public $id_klant_voor;
    /** @var string */
    public $datum;
    /** @var integer */
    public $id_vestiging;
    /** @var string */
    public $merk;
    /** @var string */
    public $type;
    /** @var string */
    public $modelserie;
    /** @var string */
    public $modelvan;
    /** @var string */
    public $modeltot;
    /** @var string */
    public $carrosserie;
    /** @var integer */
    public $jaardeel1;
    /** @var integer|null */
    public $maanddeel1;
    /** @var integer */
    public $km_tax;
    /** @var integer */
    public $deuren;
    /** @var string */
    public $kleur;
    /** @var string|null */
    public $fabkleur;
    /** @var boolean */
    public $metallic;
    /** @var string */
    public $brandstof;
    /** @var string */
    public $schakeling;
    /** @var integer */
    public $cylinder;
    /** @var integer */
    public $cylinderinhoud;
    /** @var integer */
    public $vermogenkw;
    /** @var integer */
    public $vermogenpk;
    /** @var integer|null */
    public $versnelling;
    /** @var string|null */
    public $aandrijving;
    /** @var string */
    public $datum_binnen;
    /** @var string|null */
    public $datum_lead;
    /** @var string|null */
    public $datum_verwacht;
    /** @var string|null */
    public $datum_verkocht;
    /** @var string|null */
    public $datum_internet;
    /** @var string|null */
    public $datum_internet_checked;
    /** @var string|null */
    public $afleverdatum;
    /** @var integer */
    public $km_binnen;
    /** @var string */
    public $toelating;
    /** @var string */
    public $deel1;
    /** @var string|null */
    public $deel2;
    /** @var string */
    public $apk;
    /** @var integer */
    public $bpm;
    /** @var integer|null */
    public $restbpm_lopend;
    /** @var integer|null */
    public $restbpm_binnen;
    /** @var integer|null */
    public $restbpm_verkocht;
    /** @var string */
    public $btw;
    /** @var string|null */
    public $interieur;
    /** @var string|null */
    public $kleur_interieur;
    /** @var string */
    public $nap;
    /** @var string|null */
    public $soort;
    /** @var string|null */
    public $bestemming;
    /** @var string|null */
    public $opmerkingen;
    /** @var string */
    public $status;
    /** @var string|null */
    public $status_compleet;
    /** @var integer|null */
    public $inkoopprijs;
    /** @var integer|null */
    public $verkoopprijs;
    /** @var integer|null */
    public $exbtwprijs;
    /** @var integer|null */
    public $verkoopprijs_fin;
    /** @var integer */
    public $kostenrijklaar;
    /** @var string */
    public $transport;
    /** @var string */
    public $poets;
    /** @var string|null */
    public $chassisnr;
    /** @var boolean */
    public $vkmelden;
    /** @var string|null */
    public $sleutelnr;
    /** @var integer|null */
    public $sleutels;
    /** @var string */
    public $updated;
    /** @var boolean */
    public $extern;
    /** @var mixed|null */
    public $natcode;
    /** @var integer */
    public $svid_merk;
    /** @var integer */
    public $svid_modelserie;
    /** @var integer */
    public $svid_brandstof;
    /** @var integer */
    public $svid_carrosserie;
    /** @var integer */
    public $svid_kleur;
    /** @var integer */
    public $svid_kleur_interieur;
    /** @var integer */
    public $svid_schakeling;
    /** @var integer */
    public $last_updated_by_gebruikers_id;
    /** @var string */
    public $datasource;
    /** @var integer|null */
    public $altcode;
    /** @var mixed|null */
    public $external_id;
    /** @var string|null */
    public $carshare_externe_partij;
    /** @var string|null */
    public $carshare_geldig_tot;
    /** @var boolean */
    public $is_archived;
    /** @var string|null */
    public $carshare_status;
    /** @var boolean */
    public $once_in_rdw;
    /** @var integer */
    public $tellerstand;
}