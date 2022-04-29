<?php

namespace AtpCore\Laminas\InputFilter;

use Laminas\InputFilter\InputFilter;
use Laminas\I18n\Validator\PhoneNumber;

class PhoneNumberInputFilter
{

    /**
     * Get InputFilter for a (dutch) phone-number-type field
     *
     * @param $name
     * @param bool $required
     * @param array $allowedTypes
     * @param string $countryCode
     * @return InputFilter
     */
    public static function getFilter($name, $required = false, $allowedTypes = null, $countryCode = "nl")
    {
        if ($name == null) {
            return null;
        } else {
            $filter = [
                'name' => $name,
                'required' => $required,
                'filters' => [],
                'validators' => [
                    [
                        'name' => PhoneNumber::class,
                        'options' => [
                            'country' => $countryCode,
                            'allowed_types' => (!empty($allowedTypes)) ? $allowedTypes : null,
                        ],
                    ],
                ],
            ];

            $inputFilter = new InputFilter();
            $inputFilter->add($filter, $name);
            return $inputFilter;
        }
    }

}