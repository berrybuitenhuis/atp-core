<?php

namespace AtpCore\Laminas\InputFilter;

use Laminas\Filter\StripTags;
use Laminas\Filter\StringTrim;
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
                    ['name' => StripTags::class],
                    ['name' => StringTrim::class],
                ],
            ];

            $inputFilter = new InputFilter();
            $inputFilter->add($filter, $name);
            return $inputFilter;
        }
    }

}