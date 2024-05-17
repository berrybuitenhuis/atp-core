<?php

namespace AtpCore\Api\Autotelex\Response;

class Vehicle
{
    /** @var AlternativeType[] */
    public $alternatieveUitvoeringen;
    /** @var BasicData */
    public $basisGegevens;
    /** @var string|null */
    public $companyStock;
    /** @var string|null */
    public $companyStockStatus;
    /** @var OwnerHistoryRdwData */
    public $eigenaarHistorieRDWGegevens;
    /** @var HeaderCalculation */
    public $headerBerekening;
    /** @var string|null */
    public $importData;
    /** @var ImportReportData */
    public $importReportData;
    /** @var string|null */
    public $licensePlateCheckData;
    /** @var string|null */
    public $licenseplateCheckPostalData;
    /** @var RdwMileageVerdict|null */
    public $rdwMileageVerdict;
    /** @var SecurityCertificates */
    public $securityCertificates;
    /** @var Status */
    public $status;
    /** @var string|null */
    public $tco;
    /** @var ExtendedData */
    public $uitgebreideGegevens;
    /** @var VehicleVariables */
    public $voertuigVariabelen;
    /** @var ValueData */
    public $waardeGegevens;
}