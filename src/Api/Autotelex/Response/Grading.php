<?php

namespace AtpCore\Api\Autotelex\Response;

class Grading
{
    /** @var integer */
    public $conditionExterior;
    /** @var integer */
    public $conditionInterior;
    /** @var integer|null */
    public $electric;
    /** @var integer|null */
    public $glass;
    /** @var integer|null */
    public $missingItems;
    /** @var integer|null */
    public $technicalConditionOfDrivetrainAndChassis;
    /** @var integer|null */
    public $technicalConditionOfEngine;
}