<?php

namespace AtpCore\Zf\InputFilter;

use Zend\InputFilter\InputFilter;

class PasswordInputFilter
{

    /**
     * Get InputFilter for a Password-type field
     *
     * @param $name
     * @param boolean $required
     * @param integer $minLength
     * @return void|InputFilter
     */
    public function getFilter($name, $required = false, $minLength = 8, $minUppercase = 1, $minLowercase = 1, $minDigits = 1, $minSpecialCharacters = 1)
    {
        if ($name == null) {
            return;
        } else {
            $filter = [
                'name'      => $name,
                'required'  => $required,
                'filters'   => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'min' => $minLength,
                            'max' => 250,
                        ]
                    ],[
                        'name' => 'Regex',
                        'options' => [
                            'pattern' => '/[A-Z]{' . $minUppercase . ',}/',
                        ]
                    ],[
                        'name' => 'Regex',
                        'options' => [
                            'pattern' => '/[a-z]{' . $minLowercase . ',}/',
                        ]
                    ],[
                        'name' => 'Regex',
                        'options' => [
                            'pattern' => '/[0-9]{' . $minDigits . ',}/',
                        ]
                    ],[
                        'name' => 'Regex',
                        'options' => [
                            'pattern' => '/[!@$%^&*()<>,.?\/[\]{}=_+-]{' . $minSpecialCharacters . ',}/',
                        ]
                    ],
                ],
            ];

            $inputFilter = new InputFilter();
            $inputFilter->add($filter, $name);
            return $inputFilter;
        }
    }

}