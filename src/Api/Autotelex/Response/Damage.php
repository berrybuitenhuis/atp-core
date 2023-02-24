<?php

namespace AtpCore\Api\Autotelex\Response;

class Damage
{
    /** @var integer */
    public $schadeBedrag;
    /** @var DamageImageId */
    public $schadeFotoIds;
    /** @var integer */
    public $schadeIndex;
    /** @var string */
    public $schadeOnderdeelId;
    /** @var string */
    public $schadeOnderdeelTekst;
    /** @var DamageImageUrl */
    public $schadefotoURLs;
    /** @var string|null */
    public $soortSchade;
    /** @var string */
    public $soortSchadeId;
    /** @var string|null */
    public $staat;
}