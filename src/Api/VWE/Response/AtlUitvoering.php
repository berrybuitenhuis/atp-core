<?php

namespace AtpCore\Api\VWE\Response;

class AtlUitvoering
{
    /** @var int */
    public $atlCode;
    /** @var string */
    public $uitvoering;
    /** @var string */
    public $uitvoering_lang;
    /** @var string|null */
    public $fabrieksCode;
    /** @var string */
    public $uitvoeringGeldigVanaf;
    /** @var string */
    public $uitvoeringGeldigTot;
    /** @var int */
    public $uitvoeringItem_ranking;
}