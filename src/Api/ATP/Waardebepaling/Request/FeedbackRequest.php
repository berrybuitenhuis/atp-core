<?php

namespace AtpCore\Api\ATP\Waardebepaling\Request;

class FeedbackRequest extends BaseRequest
{
    public string $rating; // good, bad
    public ?string $person = null;
    public ?string $comment = null;
}
