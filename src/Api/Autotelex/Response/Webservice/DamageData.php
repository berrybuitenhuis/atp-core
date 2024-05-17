<?php

namespace AtpCore\Api\Autotelex\Response\Webservice;

class DamageData
{
    /** @var string|null */
    public $schaderapportDatum;
    /** @var string|null */
    public $schaderapportOpmerking;
    /** @var Damages|null */
    public $schades;
    /** @var boolean */
    public $schadevrij;
    /** @var integer|null */
    public $totaleSchadekosten;
}