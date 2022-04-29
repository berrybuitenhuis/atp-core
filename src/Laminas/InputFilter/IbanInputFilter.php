<?php

namespace AtpCore\Laminas\InputFilter;

use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Iban;

class IbanInputFilter
{

    /**
     * Get InputFilter for an IBAN-field
     *
     * @param $name
     * @param bool $required
     * @return InputFilter
     */
    public static function getFilter($name, $required = false, $allowNonSepa = false)
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
                        'name' => Iban::class,
                        'options' => [
                            'allow_non_sepa' => $allowNonSepa,
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