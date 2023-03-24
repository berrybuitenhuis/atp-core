<?php

namespace AtpCore\Api\CarCollect\Response;

class Technical
{
    /** @var string|null */
    public $maintenance_last;
    /** @var string|null */
    public $technical_condition;
    /** @var boolean */
    public $technical_damage_free;
    /** @var string|null */
    public $technical_notes;
    /** @var boolean|null */
    public $timing_belt_replaced;
}