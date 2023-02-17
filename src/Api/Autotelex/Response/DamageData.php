<?php

namespace AtpCore\Api\Autotelex\Response;

class DamageData
{
    /** @var string|null */
    public $schaderapportDatum;
    /** @var string|null */
    public $schaderapportOpmerking;
    /** @var Damages */
    public $schades;
    /** @var boolean */
    public $schadevrij;
    /** @var integer|null */
    public $totaleSchadekosten;
}