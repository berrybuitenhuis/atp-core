<?php

namespace AtpCore\Api\Autotelex\Response;

class ExtendedData
{
    /** @var double */
    public $afschrijvingspercentage;
    /** @var string|null */
    public $approvalDate;
    /** @var string|null */
    public $approvalNumber;
    /** @var integer */
    public $bpm;
    /** @var integer|null */
    public $basisPercentage;
    /** @var integer */
    public $berekendeOptiebedrag;
    /** @var string|null */
    public $bijtellingHelpText;
    /** @var integer|null */
    public $bijtellingHelpTextId;
    /** @var integer|null */
    public $bijtellingspercentage;
    /** @var string|null */
    public $bijtellingspercentageGeldigTot;
    /** @var boolean|null */
    public $dubbeleCabine;
    /** @var string|null */
    public $gemiddeldeStatijd;
    /** @var double|null */
    public $importPurchaseValueDeviation;
    /** @var boolean|null */
    public $importPurchaseValueDeviationAlert;
    /** @var integer|null */
    public $importPurchaseValueLicensePlate;
    /** @var integer|null */
    public $importPurchaseValueTaxRecord;
    /** @var boolean|null */
    public $isTaxi;
    /** @var string|null */
    public $kentekenStatus;
    /** @var string|null */
    public $kleur;
    /** @var integer */
    public $maximumMassaAutonoomGeremd;
    /** @var integer */
    public $maximumMassaMiddenasGeremd;
    /** @var string|null */
    public $motorCode;
    /** @var integer */
    public $nieuwprijs;
    /** @var integer|null */
    public $nieuwprijsGrenswaarde;
    /** @var string|null */
    public $pseudoEtgCode;
    /** @var string|null */
    public $rdwBrandstofverbruikBuitenweg;
    /** @var string|null */
    public $rdwBrandstofverbruikGecombineerd;
    /** @var string|null */
    public $rdwBrandstofverbruikStad;
    /** @var integer */
    public $rdwBreedteVoertuigMax;
    /** @var string|null */
    public $rdwCallDate;
    /** @var string|null */
    public $rdwCo2Emissie;
    /** @var string|null */
    public $rdwDatumAansprakelijkheid;
    /** @var string|null */
    public $rdwDatumEersteToelatingInternationaal;
    /** @var string|null */
    public $rdwDatumEersteToelatingNationaal;
    /** @var string|null */
    public $rdwDatumRegistratieGoedkeuring;
    /** @var string|null */
    public $rdwDatumVervalApk;
    /** @var string|null */
    public $rdwEmissiecode;
    /** @var string|null */
    public $rdwEnergielabel;
    /** @var string|null */
    public $rdwEuroklasse;
    /** @var boolean|null */
    public $rdwExportIndicator;
    /** @var integer */
    public $rdwFijnstof;
    /** @var boolean */
    public $rdwG3Installatie;
    /** @var boolean|null */
    public $rdwGestolenIndicator;
    /** @var string|null */
    public $rdwHandelsbenaming;
    /** @var integer */
    public $rdwHoogteVoertuigMax;
    /** @var string|null */
    public $rdwInrichtingscode;
    /** @var string|null */
    public $rdwKleur2;
    /** @var integer */
    public $rdwLaadvermogen;
    /** @var integer */
    public $rdwLengteVoertuigMax;
    /** @var integer */
    public $rdwMassaLedigVoertuig;
    /** @var integer */
    public $rdwMassaRijklaar;
    /** @var string|null */
    public $rdwMaximaleConstructieSnelheid;
    /** @var integer */
    public $rdwMaximumMassa;
    /** @var integer */
    public $rdwMaximumMassaGeremd;
    /** @var integer */
    public $rdwMaximumMassaOngeremd;
    /** @var string|null */
    public $rdwMerk;
    /** @var boolean */
    public $rdwNieuwPrijsBekend;
    /** @var boolean */
    public $rdwParallelImport;
    /** @var integer */
    public $rdwStaanplaatsen;
    /** @var boolean|null */
    public $rdwTenaamstellenMogelijk;
    /** @var string|null */
    public $rdwUitvoeringscode;
    /** @var boolean|null */
    public $rdwVermistIndicator;
    /** @var string|null */
    public $rdwVoertuigClassificatie;
    /** @var integer */
    public $rdwVoertuigClassificatieCode;
    /** @var boolean|null */
    public $rdwWamVerzekerd;
    /** @var boolean|null */
    public $rdwWokStatusIndicator;
    /** @var integer */
    public $rdwZitplaatsen;
    /** @var integer|null */
    public $residualBpmAmountOnArrivalDate;
    /** @var integer */
    public $restBpm;
    /** @var Status */
    public $status;
    /** @var boolean|null */
    public $taxiVerleden;
    /** @var string|null */
    public $typeCarrosserieEuOmschrijving;
    /** @var string|null */
    public $typeCode;
    /** @var string|null */
    public $variantCode;
    /** @var string|null */
    public $voertuigsoort;
    /** @var string|null */
    public $wijzeVanInvoer;
    /** @var integer|null */
    public $wijzeVanInvoerHelpTextId;
}