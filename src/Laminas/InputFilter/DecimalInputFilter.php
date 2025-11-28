<?php

namespace AtpCore\Laminas\InputFilter;

use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Regex;

class DecimalInputFilter
{

    /**
     * Get InputFilter for a Decimal-type field
     *
     * @param $name
     * @param int $precision, total length of value
     * @param int $scale, length of decimals
     * @param bool $required
     * @return InputFilter
     */
    public static function getFilter($name, $precision, $scale, $required = false)
    {
        if ($name == null) {
            return null;
        } else {
            $filter = [
                'name'      => $name,
                'required'  => $required,
                'validators'   => [
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/^\d{1,' . ($precision - $scale) . '}(\.\d{1,' . $scale . '})?$/',
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