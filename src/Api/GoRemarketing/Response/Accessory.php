<?php

namespace AtpCore\Api\GoRemarketing\Response;

class Accessory
{
    /** @var integer */
    public $id;
    /** @var integer */
    public $auto_id;
    /** @var string */
    public $groep;
    /** @var string */
    public $omschrijving;
    /** @var string */
    public $omschrijving_lang;
    /** @var string */
    public $bedrag;
    /** @var integer */
    public $select;
    /** @var mixed|null */
    public $mancode;
    /** @var mixed|null */
    public $et_code;
    /** @var mixed|null */
    public $et_groupcode;
    /** @var mixed|null */
    public $et_package;
    /** @var integer */
    public $highlight;
}