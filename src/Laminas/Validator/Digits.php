<?php

namespace AtpCore\Laminas\Validator;

use Laminas\Validator\Digits AS LaminasDigits;

class Digits extends LaminasDigits
{
    /**
     * Sets validator options
     *
     * @param  array|Traversable $options
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        $this->setMessage("The input (%value%) must contain only digits", self::NOT_DIGITS);
    }
}
