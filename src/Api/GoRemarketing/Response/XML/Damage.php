<?php

namespace AtpCore\Api\GoRemarketing\Response\XML;

class Damage
{
    /** @var string */
    public $soort;
    /** @var string|null */
    public $locatie;
    /** @var integer */
    public $kosten;
    /** @var string */
    public $omschrijving;
    /** @var Images */
    public $fotos;
}