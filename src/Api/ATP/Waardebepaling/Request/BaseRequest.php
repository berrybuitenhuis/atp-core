<?php

namespace AtpCore\Api\ATP\Waardebepaling\Request;

abstract class BaseRequest implements \JsonSerializable
{
    /**
     * Serialize request without null-values (unset fields are not sent)
     */
    public function jsonSerialize(): array
    {
        return array_filter(get_object_vars($this), fn($value) => $value !== null);
    }
}
