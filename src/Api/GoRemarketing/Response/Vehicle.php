<?php

namespace AtpCore\Api\GoRemarketing\Response;

class Vehicle
{
    /** @var Config */
    public $config;
    /** @var Car */
    public $auto;
    /** @var Environment */
    public $milieu;
    /** @var Additional */
    public $additional;
    /** @var Image[] */
    public $carimages;
    /** @var Accessory[] */
    public $accessoires;
    /** @var Company */
    public $bedrijven;
    /** @var Damage[]|null */
    public $schades;
}
