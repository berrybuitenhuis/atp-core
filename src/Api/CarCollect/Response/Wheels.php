<?php

namespace AtpCore\Api\CarCollect\Response;

class Wheels
{
    /** @var string */
    public $profile_depth_left_front;
    /** @var string */
    public $profile_depth_left_rear;
    /** @var string */
    public $profile_depth_right_front;
    /** @var string */
    public $profile_depth_right_rear;
    /** @var integer|null */
    public $rim_inches;
    /** @var string|null */
    public $secondary_profile_depth_left_front;
    /** @var string|null */
    public $secondary_profile_depth_left_rear;
    /** @var string|null */
    public $secondary_profile_depth_right_front;
    /** @var string|null */
    public $secondary_profile_depth_right_rear;
    /** @var integer|null */
    public $secondary_rim_inches;
    /** @var string|null */
    public $secondary_tire_brand;
    /** @var integer|null */
    public $secondary_tire_height;
    /** @var string|null */
    public $secondary_tire_type;
    /** @var integer|null */
    public $secondary_tire_width;
    /** @var string|null */
    public $tire_brand;
    /** @var integer|null */
    public $tire_height;
    /** @var string */
    public $tire_type;
    /** @var integer|null */
    public $tire_width;
    /** @var boolean */
    public $wheels_damage_free;
    /** @var string|null */
    public $wheels_notes;
}