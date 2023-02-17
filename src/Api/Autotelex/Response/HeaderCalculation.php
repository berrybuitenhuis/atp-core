<?php

namespace AtpCore\Api\Autotelex\Response;

class HeaderCalculation
{
    /** @var integer */
    public $consumentenprijs;
    /** @var integer */
    public $consumentenprijsInclOpties;
    /** @var string */
    public $eersteToelating;
    /** @var boolean */
    public $isGevuld;
    /** @var integer */
    public $nieuwprijsAutotelex;
    /** @var integer */
    public $nieuwprijsRdw;
    /** @var integer */
    public $optieBedrag;
    /** @var string */
    public $prijslijstDatum;
    /** @var integer */
    public $selectedOptions;
}