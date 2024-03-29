<?php

namespace AtpCore\Laminas\InputFilter;

use Laminas\InputFilter\InputFilter;
use Laminas\Validator\InArray;

class BooleanInputFilter
{

    /**
     * Get InputFilter for a Boolean-type field
     *
     * @param $name
     * @return InputFilter
     */
    public static function getFilter($name)
    {
        // REQUIRED OPTION IS NOT WORKING, BECAUSE "false" IS NOT A VALID NON-EMPTY VALUE!
        // SO THIS CONSTRAINT HAS TO BE CHECKED AS "NOT NULL" IN TABLE
        if ($name == null) {
            return null;
        } else {
            $filter = [
                'name' => $name,
                'allow_empty' => true,
                'required' => false,
                'filters' => [],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [true, false, 1, 0, "1", "0"],
                            'strict' => InArray::COMPARE_STRICT,
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
