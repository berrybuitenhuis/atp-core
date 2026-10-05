<?php

namespace AtpCore\Api\Waardebepaling\Response;

class Valuation
{
    /** @var string */
    public $uuid;
    /** @var string|null */
    public $reference;
    /** @var string */
    public $status;
    /** @var string|null */
    public $current_stage;
    /** @var Progress|null */
    public $progress;
    /** @var string|null */
    public $extra_options;
    /** @var string|null */
    public $damage_description;
    /** @var object|null */
    public $vehicle;
    /** @var object|null */
    public $damage;
    /** @var array|null */
    public $images;
    /** @var object|null */
    public $normalized_input;
    /** @var array|null */
    public $warnings;
    /** @var object|null */
    public $image_analysis;
    /** @var object|null */
    public $text_analysis;
    /** @var object|null */
    public $damage_comparison;
    /** @var object|null */
    public $ai_verification;
    /** @var Result|null */
    public $latest_result;
    /** @var Comparable[]|null */
    public $comparables;
    /** @var Comparable[]|null */
    public $gaspedaal_not_counted;
    /** @var StageOutput[]|null */
    public $stage_outputs;
    /** @var string */
    public $created_at;
    /** @var string|null */
    public $started_at;
    /** @var string|null */
    public $completed_at;
    /** @var string|null */
    public $updated_at;

    /**
     * Check if valuation reached a terminal status
     */
    public function isTerminal(): bool
    {
        return !in_array($this->status, ["queued", "processing"]);
    }
}
