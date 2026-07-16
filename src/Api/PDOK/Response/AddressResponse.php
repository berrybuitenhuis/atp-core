<?php

namespace AtpCore\Api\PDOK\Response;

class AddressResponse
{
    /** @var integer */
    public $numFound;
    /** @var integer */
    public $start;
    /** @var float */
    public $maxScore;
    /** @var boolean */
    public $numFoundExact;
    /** @var AddressDocument[] */
    public $docs;
}