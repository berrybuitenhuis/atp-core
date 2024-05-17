<?php

namespace AtpCore\Api\Autotelex\Response\Webservice;

class HeaderCalculation
{
    /** @var integer|null */
    public $consumentenprijs;
    /** @var integer|null */
    public $consumentenprijsInclOpties;
    /** @var string */
    public $eersteToelating;
    /** @var boolean */
    public $isGevuld;
    /** @var integer */
    public $nieuwprijsAutotelex;
    /** @var integer|null */
    public $nieuwprijsRdw;
    /** @var integer */
    public $optieBedrag;
    /** @var string|null */
    public $prijslijstDatum;
    /** @var integer */
    public $selectedOptions;
}