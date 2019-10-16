<?php

namespace AtpCore\Zf\InputFilter;

use Zend\InputFilter\InputFilter;

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
                    ['name' => 'Digits'],
                ],
            ];

            $inputFilter = new InputFilter();
            $inputFilter->add($filter, $name);
            return $inputFilter;
        }
    }

}