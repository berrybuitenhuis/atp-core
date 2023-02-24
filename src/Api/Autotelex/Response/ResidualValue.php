<?php

namespace AtpCore\Api\Autotelex\Response;

class ResidualValue
{
    /** @var integer|null */
    public $bpm;
    /** @var integer|null */
    public $btw;
    /** @var integer */
    public $id;
    /** @var string */
    public $naam;
    /** @var double */
    public $percentage;
    /** @var integer */
    public $waarde;
    /** @var integer|null */
    public $waardeExclusief;
}