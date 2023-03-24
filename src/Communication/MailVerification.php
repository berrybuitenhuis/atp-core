<?php

namespace AtpCore\Communication;

class MailVerification {
    public bool $isValid;
    public string $result;

    public function __construct($isValid, $result) {
        $this->isValid = $isValid;
        $this->result = $result;
    }
}