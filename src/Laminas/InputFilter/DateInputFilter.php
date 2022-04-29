<?php

namespace AtpCore\Laminas\InputFilter;

use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Date;

class DateInputFilter
{

    /**
     * Get InputFilter for a Date-type field
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
                    [
                        'name' => Date::class,
                    ],
                ],
            ];

            $inputFilter = new InputFilter();
            $inputFilter->add($filter, $name);
            return $inputFilter;
        }
    }

}