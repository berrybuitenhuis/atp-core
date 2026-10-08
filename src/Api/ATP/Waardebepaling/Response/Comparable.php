<?php

namespace AtpCore\Api\ATP\Waardebepaling\Response;

class Comparable
{
    /** @var mixed */
    public $id;
    /** @var string|null */
    public $external_reference;
    /** @var float|null */
    public $similarity_score;
    /** @var object|null */
    public $similarity_breakdown;
    /** @var integer|null */
    public $comparable_value;
    /** @var integer|null */
    public $consumer_value;
    /** @var object|null */
    public $price_breakdown;
    /** @var string|null */
    public $currency;
    /** @var boolean|null */
    public $is_atp_purchase;
    /** @var boolean|null */
    public $is_import;
    /** @var string|null */
    public $source;
    /** @var object|null */
    public $vehicle_data;
    /** @var string|null */
    public $created_at;
}
