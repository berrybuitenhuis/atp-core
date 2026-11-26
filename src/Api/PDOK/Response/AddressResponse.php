<?php

namespace AtpCore\Api\PDOK\Response;

class AddressResponse
{
    /** @var integer */
    public $numFound;
    /** @var integer */
    public $start;
    /** @var double */
    public $maxScore;
    /** @var boolean */
    public $numFoundExact;
    /** @var AddressDocument[]|null */
    public $docs;
}