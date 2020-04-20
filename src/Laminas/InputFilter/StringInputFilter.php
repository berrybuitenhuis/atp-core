<?php

namespace AtpCore\Laminas\InputFilter;

use Laminas\InputFilter\InputFilter;

class StringInputFilter
{

    /**
     * Get InputFilter for a String-type field
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
                'name'      => $name,
                'required'  => $required,
                'filters'   => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
            ];

            $inputFilter = new InputFilter();
            $inputFilter->add($filter, $name);
            return $inputFilter;
        }
    }

}