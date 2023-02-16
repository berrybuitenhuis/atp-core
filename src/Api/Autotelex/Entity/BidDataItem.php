<?php

namespace AtpCore\Api\Autotelex\Entity;

class BidDataItem
{
    public $bedrijfsNaam;
    public $bidOfferType;
    public $bidOfferTypeDescription;
    public $bidderInstanceId;
    public $biedingId;
    public $datum;
    public $hbh_id;
    public $hertaxatie;
    public $inclExclBtw;
    public $isRenewable;
    public $opmerking;
    public $soort;
    public $soortNaam;
    public $status;
    public $statusNaam;
    /** @var TmStatusHistoryData */
    public $tmStatusHistorieLijst;
    public $traderName;
    public $waarde;
}