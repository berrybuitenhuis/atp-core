<?php

namespace AtpCore\Api\Autotelex\Response\Webservice;

class Grading
{
    /** @var integer|null */
    public $ConditionExterior;
    /** @var integer|null */
    public $ConditionInterior;
    /** @var integer|null */
    public $Electric;
    /** @var integer|null */
    public $Glass;
    /** @var integer|null */
    public $MissingItems;
    /** @var MissingItems2[]|null */
    public $missingItems2;
    /** @var integer|null */
    public $TechnicalConditionOfDrivetrainAndChassis;
    /** @var integer|null */
    public $TechnicalConditionOfEngine;
}
