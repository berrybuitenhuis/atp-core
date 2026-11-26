<?php

namespace AtpCore\Api\ATP\Waardebepaling\Response;

class Progress
{
    /** @var string|null */
    public $current_stage;
    /** @var string[]|null */
    public $completed_stages;
    /** @var integer|null */
    public $percent;
    /** @var integer|null */
    public $estimated_seconds_remaining;
}
