<?php

namespace AtpCore\Api\Autotelex\Response;

class TiresDataItem
{
    /** @var integer */
    public $id;
    /** @var integer|null */
    public $kosten;
    /** @var boolean|null */
    public $lichtmetaalVelgen;
    /** @var integer */
    public $positieId;
    /** @var integer|null */
    public $profielDiepte;
    /** @var integer|null */
    public $rimDiameter;
    /** @var integer */
    public $setNumber;
    /** @var integer */
    public $soortBandId;
    /** @var string */
    public $staat;
    /** @var integer */
    public $status;
}