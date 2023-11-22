<?php

namespace AtpCore\Api\GoRemarketing\Response\XML;

class Damage
{
    /** @var string|null */
    public $soort;
    /** @var string|null */
    public $locatie;
    /** @var integer */
    public $kosten;
    /** @var mixed|null */
    public $omschrijving;
    /** @var Images */
    public $fotos;
}
