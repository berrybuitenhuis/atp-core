<?php

namespace AtpCore\Api\Autotelex\Entity;

class Damage
{
    public $schadeBedrag;
    /** @var DamageImageId */
    public $schadeFotoIds;
    public $schadeIndex;
    public $schadeOnderdeelId;
    public $schadeOnderdeelTekst;
    /** @var DamageImageUrl */
    public $schadefotoURLs;
    public $soortSchade;
    public $soortSchadeId;
    public $staat;
}