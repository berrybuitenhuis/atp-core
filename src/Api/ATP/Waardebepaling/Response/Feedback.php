<?php

namespace AtpCore\Api\ATP\Waardebepaling\Response;

class Feedback
{
    /** @var integer */
    public $id;
    /** @var string */
    public $uuid;
    /** @var string|null */
    public $reference;
    /** @var string */
    public $rating;
    /** @var string|null */
    public $person;
    /** @var string|null */
    public $comment;
    /** @var string */
    public $created_at;
}
