<?php

namespace AtpCore\Laminas\InputFilter;

use Laminas\InputFilter\InputFilter;
use Laminas\Validator\NotEmpty;

class EntityInputFilter
{

    /**
     * Get InputFilter for a Entity-type field
     *
     * @param $name
     * @param boolean $required
     * @return InputFilter
     */
    public static function getFilter($name, $required = false)
    {
        if ($name == null) {
            return null;
        } else {
            if ($required === false) {
                $filter = [
                    'name' => $name,
                    'allow_empty' => true,
                ];
            } else {
                $filter = [
                    'name' => $name,
                    'validators' => [
                        [
                            'name' => NotEmpty::class,
                        ],
                    ],
                ];
            }

            $inputFilter = new InputFilter();
            $inputFilter->add($filter, $name);
            return $inputFilter;
        }
    }

}
