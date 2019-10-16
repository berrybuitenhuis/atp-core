<?php
namespace AtpCore\Api\OneSignal\Entity;

use AtpCore\Api\Base;

class Notification extends Base
{

    /** @var string */
    public $app_id;
    /** @var array */
    public $include_player_ids;
    /** @var array */
    public $data;
    /** @var array */
    public $contents;

}