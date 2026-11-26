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
    /** @var integer|null */
    public $soort_id;
    /** @var mixed|null */
    public $omschrijving;
    /** @var integer */
    public $kosten;
    /** @var string|null */
    public $external_id;
    /** @var string|null */
    public $soort;
    /** @var string|null */
    public $locatie;
    /** @var DamageImage[]|null */
    public $images;
}