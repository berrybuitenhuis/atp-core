<?php

namespace AtpCore\Communication;

class MailVerification {
    /** @var bool|null */
    public $isValid;
    /** @var string */
    public $result;

    public function __construct($isValid, $result) {
        $this->isValid = $isValid;
        $this->result = $result;
    }
}