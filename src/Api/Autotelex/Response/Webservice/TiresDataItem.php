<?php

namespace AtpCore\Api\Autotelex\Response\Webservice;

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
    /** @var double|null */
    public $profielDiepte;
    /** @var double|null */
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