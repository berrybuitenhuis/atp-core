<?php

namespace AtpCore\Api\Waardebepaling\Response;

class Result
{
    /** @var integer|null */
    public $id;
    /** @var integer|null */
    public $trade_value;
    /** @var integer|null */
    public $ai_adjusted_trade_value;
    /** @var integer|null */
    public $atp_bid;
    /** @var integer|null */
    public $consumer_value;
    /** @var string|null */
    public $currency;
    /** @var Range|null */
    public $range;
    /** @var float|null */
    public $confidence;
    /** @var boolean|null */
    public $review_required;
    /** @var object|null */
    public $market;
    /** @var object|null */
    public $adjustments;
    /** @var mixed|null */
    public $signals;
    /** @var array|null */
    public $warnings;
    /** @var object|null */
    public $meta;
    /** @var object|null */
    public $used_inputs;
}
