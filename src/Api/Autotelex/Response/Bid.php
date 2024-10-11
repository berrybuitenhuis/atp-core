<?php

namespace AtpCore\Api\Autotelex\Response;

class Bid
{
    /** @var string */
    public $bedrijfsNaam;
    /** @var integer */
    public $bidOfferType;
    /** @var string */
    public $bidOfferTypeDescription;
    /** @var integer */
    public $bidderInstanceId;
    /** @var integer */
    public $biedingId;
    /** @var string */
    public $datum;
    /** @var string|null */
    public $geldigTot;
    /** @var integer */
    public $hbh_id;
    /** @var boolean */
    public $hertaxatie;
    /** @var boolean|null */
    public $inclExclBtw;
    /** @var boolean|null */
    public $isPublicBid;
    /** @var boolean */
    public $isRenewable;
    /** @var string|null */
    public $opmerking;
    /** @var string|null */
    public $reagerenTot;
    /** @var integer */
    public $soort;
    /** @var string */
    public $soortNaam;
    /** @var integer */
    public $status;
    /** @var string */
    public $statusNaam;
    /** @var TmStatusHistoryData[] */
    public $tmStatusHistorieLijst;
    /** @var string */
    public $traderName;
    /** @var integer */
    public $waarde;
}
