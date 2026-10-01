<?php

namespace AtpCore\Api\GoRemarketing\Response;

class Damage
{
    /** @var integer */
    public $id;
    /** @var string */
    public $created;
    /** @var string */
    public $updated;
    /** @var integer */
    public $auto_id;
    /** @var integer */
    public $locatie_id;
    /** @var integer */
    public $soort_id;
    /** @var string */
    public $omschrijving;
    /** @var integer */
    public $kosten;
    /** @var string|null */
    public $external_id;
    /** @var string */
    public $soort;
    /** @var string */
    public $locatie;
    /** @var DamageImage[] */
    public $images;
}