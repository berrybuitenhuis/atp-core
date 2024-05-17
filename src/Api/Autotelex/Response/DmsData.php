<?php

namespace AtpCore\Api\Autotelex\Response;

class DmsData
{
    /** @var integer|null */
    public $dmsBuyInPrice;
    /** @var boolean|null */
    public $dmsBuyInPriceInclVat;
    /** @var string|null */
    public $dmsDeliveryDate;
    /** @var integer|null */
    public $dmsExpectedSellPrice;
    /** @var integer|null */
    public $dmsTradeInPrice;
}