<?php

namespace AtpCore\Laminas\InputFilter;

use Laminas\InputFilter\InputFilter;

class IntegerInputFilter
{

    /**
     * Get InputFilter for a Integer-type field
     *
     * @param $name
     * @param bool $required
     * @return InputFilter
     */
    public static function getFilter($name, $required = false)
    {
        if ($name == null) {
            return null;
        } else {
            $filter = [
                'name' => $name,
                'required' => $required,
                'validators' => [
                    ['name' => \AtpCore\Laminas\Validator\Digits::class],
                ],
            ];

            $inputFilter = new InputFilter();
            $inputFilter->add($filter, $name);
            return $inputFilter;
        }
    }

}