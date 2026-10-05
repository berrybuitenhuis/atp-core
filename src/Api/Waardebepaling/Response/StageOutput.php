<?php

namespace AtpCore\Api\Waardebepaling\Response;

class StageOutput
{
    /** @var string */
    public $stage;
    /** @var string|null */
    public $status;
    /** @var integer|null */
    public $duration_ms;
    /** @var string|null */
    public $started_at;
    /** @var string|null */
    public $finished_at;
}
