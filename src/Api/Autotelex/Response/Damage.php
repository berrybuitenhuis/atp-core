<?php

namespace AtpCore\Api\Autotelex\Response;

class Damage
{
    /** @var integer */
    public $schadeBedrag;
    /** @var integer[]|null */
    public $schadeFotoIds;
    /** @var integer */
    public $schadeIndex;
    /** @var string */
    public $schadeOnderdeelId;
    /** @var string */
    public $schadeOnderdeelTekst;
    /** @var string[]|null */
    public $schadefotoURLs;
    /** @var string|null */
    public $soortSchade;
    /** @var string */
    public $soortSchadeId;
    /** @var string|null */
    public $staat;
}