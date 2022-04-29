<?php

namespace AtpCore\Laminas\InputFilter;

use Laminas\Filter\StripTags;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

class PasswordInputFilter
{

    /**
     * Get InputFilter for a Password-type field
     *
     * @param string $name
     * @param boolean $required
     * @param integer $minLength
     * @param integer $minUppercase
     * @param integer $minLowercase
     * @param integer $minDigits
     * @param integer $minSpecialCharacters
     * @return InputFilter
     */
    public static function getFilter($name, $required = false, $minLength = 8, $minUppercase = 1, $minLowercase = 1, $minDigits = 1, $minSpecialCharacters = 1)
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
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => $minLength,
                            'max' => 250,
                        ]
                    ],[
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/[A-Z]{' . $minUppercase . ',}/',
                        ]
                    ],[
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/[a-z]{' . $minLowercase . ',}/',
                        ]
                    ],[
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/[0-9]{' . $minDigits . ',}/',
                        ]
                    ],[
                        'name' => Regex::class,
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