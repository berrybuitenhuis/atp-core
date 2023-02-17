<?php

namespace AtpCore\Api\Autotelex\Response;

class DamageData
{
    public $schaderapportDatum;
    public $schaderapportOpmerking;
    /** @var Damages */
    public $schades;
    public $schadevrij;
    public $totaleSchadekosten;
}