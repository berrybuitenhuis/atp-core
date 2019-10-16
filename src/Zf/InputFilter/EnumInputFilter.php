<?php

namespace AtpCore\Zf\InputFilter;

use Zend\InputFilter\InputFilter;
use Zend\Validator\InArray;

class EnumInputFilter
{

    /**
     * Get InputFilter for a Enum-type field
     *
     * @param $name
     * @param bool $required
     * @param array $enumValues
     * @return InputFilter
     */
    public static function getFilter($name, $required = false, $enumValues = [])
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
                        'name' => 'InArray',
                        'options' => [
                            'haystack' => $enumValues,
                            'strict' => InArray::COMPARE_STRICT
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