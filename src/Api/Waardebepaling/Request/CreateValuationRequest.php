<?php

namespace AtpCore\Api\Waardebepaling\Request;

class CreateValuationRequest extends BaseRequest
{
    public ?string $reference = null;
    public ?string $source = null;
    public ?string $extra_options = null;
    public ?string $damage_description = null;
    public ?Context $context = null;
    public Vehicle $vehicle;
    public ?Condition $condition = null;
    public ?Damage $damage = null;
    /** @var Image[]|null */
    public ?array $images = null;
    public ?Rules $rules = null;
    public ?Config $config = null;
}
