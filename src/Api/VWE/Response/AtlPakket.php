<?php

namespace AtpCore\Api\VWE\Response;

class AtlPakket
{
    /** @var string */
    public $naam;
    /** @var int */
    public $pakket_id;
    /** @var int|null */
    public $opakket_bedrag;
    /** @var AtlOpties */
    public $opties;
}