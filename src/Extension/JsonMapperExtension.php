<?php

namespace AtpCore\Extension;

use AtpCore\Format;
use JsonMapper;

class JsonMapperExtension extends JsonMapper {

    public function __construct()
    {
        $this->resetErrors();
    }

    public $bCastToExpectedType = false;
    protected $errorData = null;
    protected $messages = [];
    /**
     * Set error-data
     *
     * @param $data
     */
    public function setErrorData($data)
    {
        $this->errorData = $data;
    }

    /**
     * Get error-data
     *
     * @return array
     */
    public function getErrorData()
    {
        return $this->errorData;
    }

    /**
     * Set error-message
     *
     * @param array|string $messages
     */
    public function setMessages($messages)
    {
        if (!is_array($messages)) $messages = [$messages];
        $this->messages = $messages;
    }

    /**
     * Add error-message
     *
     * @param array|string $message
     */
    public function addMessage($message)
    {
        if (!is_array($message)) $message = [$message];
        $this->messages = array_merge($this->messages, $message);
    }

    /**
     * Get error-messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Reset error-messages and error-data
     */
    public function resetErrors()
    {
        $this->messages = [];
        $this->errorData = [];
    }

    /**
     * Check if data is valid by data-types
     *
     * @param string $data
     * @param string $expectedType
     * @return bool
     */
    public function isValid($data, $expectedType)
    {
        list($namespace, $name) = $this->getClassDetails($data);
        return $this->validateData($name, $namespace, $expectedType, false, $data);
    }

    // WARNING: we overrule method of library because "settype()" is always executed (instead of optional)
    public function map($json, $object)
    {
        if ($this->bEnforceMapType && !is_object($json)) {
            throw new \InvalidArgumentException(
                'JsonMapper::map() requires first argument to be an object'
                . ', ' . gettype($json) . ' given.'
            );
        }
        if (!is_object($object)) {
            throw new \InvalidArgumentException(
                'JsonMapper::map() requires second argument to be an object'
                . ', ' . gettype($object) . ' given.'
            );
        }

        $strClassName = get_class($object);
        $rc = new \ReflectionClass($object);
        $strNs = $rc->getNamespaceName();
        $providedProperties = array();
        foreach ($json as $key => $jvalue) {
            $key = $this->getSafeName($key);
            $providedProperties[$key] = true;

            // Overwrite empty array into null
            if (is_array($jvalue) && empty($jvalue)) {
                $jvalue = null;
            }

            // Store the property inspection results so we don't have to do it
            // again for subsequent objects of the same type
            if (!isset($this->arInspectedClasses[$strClassName][$key])) {
                $this->arInspectedClasses[$strClassName][$key]
                    = $this->inspectProperty($rc, $key);
            }

            list($hasProperty, $accessor, $type, $isNullable)
                = $this->arInspectedClasses[$strClassName][$key];

            if (!$hasProperty) {
                if ($this->bExceptionOnUndefinedProperty) {
                    throw new \JsonMapper_Exception(
                        'JSON property "' . $key . '" (type: ' . gettype($jvalue) . ') does not exist'
                        . ' in object of type ' . $strClassName
                    );
                } else if ($this->undefinedPropertyHandler !== null) {
                    call_user_func(
                        $this->undefinedPropertyHandler,
                        $object, $key, $jvalue
                    );
                } else {
                    $this->log(
                        'info',
                        'Property {property} does not exist in {class}',
                        array('property' => $key, 'class' => $strClassName)
                    );
                }
                continue;
            }

            if ($accessor === null) {
                if ($this->bExceptionOnUndefinedProperty) {
                    throw new \JsonMapper_Exception(
                        'JSON property "' . $key . '" has no public setter method'
                        . ' in object of type ' . $strClassName
                    );
                }
                $this->log(
                    'info',
                    'Property {property} has no public setter method in {class}',
                    array('property' => $key, 'class' => $strClassName)
                );
                continue;
            }

            if ($isNullable || !$this->bStrictNullTypes) {
                if ($jvalue === null) {
                    $this->setProperty($object, $accessor, null);
                    continue;
                }
                $type = $this->removeNullable($type);
            } else if ($jvalue === null) {
                throw new \JsonMapper_Exception(
                    'JSON property "' . $key . '" in class "'
                    . $strClassName . '" must not be NULL'
                );
            }

            $type = $this->getFullNamespace($type, $strNs);
            $type = $this->getMappedType($type, $jvalue);

            if ($type === null || $type === 'mixed') {
                //no given type - simply set the json data
                $this->setProperty($object, $accessor, $jvalue);
                continue;
            } else if ($this->isObjectOfSameType($type, $jvalue)) {
                $this->setProperty($object, $accessor, $jvalue);
                continue;
            } else if ($this->isSimpleType($type)) {
                if ($type === 'string' && is_object($jvalue)) {
                    throw new \JsonMapper_Exception(
                        'JSON property "' . $key . '" in class "'
                        . $strClassName . '" is an object and'
                        . ' cannot be converted to a string'
                    );
                }
                if ($this->bCastToExpectedType === true) {
                    settype($jvalue, $type);
                }
                $this->setProperty($object, $accessor, $jvalue);
                continue;
            }

            //FIXME: check if type exists, give detailed error message if not
            if ($type === '') {
                throw new \JsonMapper_Exception(
                    'Empty type at property "'
                    . $strClassName . '::$' . $key . '"'
                );
            } else if (strpos($type, '|')) {
                throw new \JsonMapper_Exception(
                    'Cannot decide which of the union types shall be used: '
                    . $type
                );
            }

            $array = null;
            $subtype = null;
            if ($this->isArrayOfType($type)) {
                //array
                $array = array();
                $subtype = substr($type, 0, -2);
            } else if (substr($type, -1) == ']') {
                list($proptype, $subtype) = explode('[', substr($type, 0, -1));
                if ($proptype == 'array') {
                    $array = array();
                } else {
                    $array = $this->createInstance($proptype, false, $jvalue);
                }
            } else {
                if (is_a($type, 'ArrayObject', true)) {
                    $array = $this->createInstance($type, false, $jvalue);
                }
            }

            if ($array !== null) {
                if (!is_array($jvalue) && $this->isFlatType(gettype($jvalue))) {
                    throw new \JsonMapper_Exception(
                        'JSON property "' . $key . '" must be an array, '
                        . gettype($jvalue) . ' given'
                    );
                }

                $cleanSubtype = $this->removeNullable($subtype);
                $subtype = $this->getFullNamespace($cleanSubtype, $strNs);
                $child = $this->mapArray($jvalue, $array, $subtype, $key);
            } else if ($this->isFlatType(gettype($jvalue))) {
                //use constructor parameter if we have a class
                // but only a flat type (i.e. string, int)
                if ($this->bStrictObjectTypeChecking) {
                    throw new \JsonMapper_Exception(
                        'JSON property "' . $key . '" must be an object, '
                        . gettype($jvalue) . ' given'
                    );
                }
                $child = $this->createInstance($type, true, $jvalue);
            } else {
                $child = $this->createInstance($type, false, $jvalue);
                $this->map($jvalue, $child);
            }
            $this->setProperty($object, $accessor, $child);
        }

        if ($this->bExceptionOnMissingData) {
            $this->checkMissingData($providedProperties, $rc);
        }

        if ($this->bRemoveUndefinedAttributes) {
            $this->removeUndefinedAttributes($object, $providedProperties);
        }

        if ($this->postMappingMethod !== null
            && $rc->hasMethod($this->postMappingMethod)
        ) {
            $refDeserializePostMethod = $rc->getMethod(
                $this->postMappingMethod
            );
            $refDeserializePostMethod->setAccessible(true);
            $refDeserializePostMethod->invoke($object);
        }

        return $object;
    }

    // WARNING: we overrule method of library because "settype()" is always executed (instead of optional)
    public function mapArray($json, $array, $class = null, $parent_key = '')
    {
        $originalClass = $class;
        foreach ($json as $key => $jvalue) {
            $class = $this->getMappedType($originalClass, $jvalue);
            if ($class === null) {
                $array[$key] = $jvalue;
            } else if ($this->isArrayOfType($class)) {
                $array[$key] = $this->mapArray(
                    $jvalue,
                    array(),
                    substr($class, 0, -2)
                );
            } else if ($this->isFlatType(gettype($jvalue))) {
                //use constructor parameter if we have a class
                // but only a flat type (i.e. string, int)
                if ($jvalue === null) {
                    $array[$key] = null;
                } else {
                    if ($this->isSimpleType($class)) {
                        if ($this->bCastToExpectedType === true) {
                            settype($jvalue, $class);
                        }
                        $array[$key] = $jvalue;
                    } else {
                        $array[$key] = $this->createInstance(
                            $class, true, $jvalue
                        );
                    }
                }
            } else if ($this->isFlatType($class)) {
                throw new \JsonMapper_Exception(
                    'JSON property "' . ($parent_key ? $parent_key : '?') . '"'
                    . ' is an array of type "' . $class . '"'
                    . ' but contained a value of type'
                    . ' "' . gettype($jvalue) . '"'
                );
            } else if (is_a($class, 'ArrayObject', true)) {
                $array[$key] = $this->mapArray(
                    $jvalue,
                    $this->createInstance($class)
                );
            } else {
                $array[$key] = $this->map(
                    $jvalue, $this->createInstance($class, false, $jvalue)
                );
            }
        }
        return $array;
    }

    /**
     * Get class-details from data
     *
     * @param mixed $data
     * @return array
     */
    private function getClassDetails($data)
    {
        $className = get_class($data);
        $reflectionClass = new \ReflectionClass($className);
        return [$reflectionClass->getNamespaceName(), $reflectionClass->getShortName()];
    }

    private function getPropertyDetails($className, $propertyName)
    {
        $reflectionClass = new \ReflectionClass($className);
        $property = $this->inspectProperty($reflectionClass, $propertyName);
        $this->arInspectedClasses[$className][$propertyName] = $property;
        return $property;
    }

    private function getScheme($objectName, $propertyName)
    {
        if (!empty($this->arInspectedClasses[$objectName])) {
            $objectProperties = array_change_key_case($this->arInspectedClasses[$objectName], CASE_LOWER);
            if (array_key_exists(Format::lowercase($propertyName), $objectProperties)) {
                $scheme = $objectProperties[Format::lowercase($propertyName)];
            } else {
                $scheme = $this->getPropertyDetails($objectName, $propertyName);
            }
        } else {
            $scheme = $this->getPropertyDetails($objectName, $propertyName);
        }
        if (empty($scheme)) {
            $this->addMessage("No scheme found for $propertyName");
            return false;
        }

        // Return
        return $scheme;
    }

    /**
     * Validate data-type
     *
     * @param string $name
     * @param string $expectedType
     * @param mixed $data
     * @return boolean
     */
    private function validate($name, $expectedType, $data) {
        // Set data-type
        $dataType = gettype($data);
        if ($dataType == "object") {
            $dataType = get_class($data);
            if ($dataType == "stdClass") $dataType = "\\$dataType";
        }

        // Validate data-type
        $valid = ($expectedType == $dataType || ($expectedType == "mixed" && in_array($dataType, ["integer","string"])));
        if ($valid === false) {
            $this->addMessage("Invalid data-type for $name (expected: $expectedType, actual: $dataType)");
        }

        // Return
        return $valid;
    }

    /**
     * Validate data by data-type
     *
     * @param string $name
     * @param string $namespace
     * @param string $expectedType
     * @param boolean $nullable
     * @param mixed $data
     * @return boolean
     */
    private function validateData($name, $namespace, $expectedType, $nullable, $data)
    {
        switch(Format::lowercase(gettype($data))) {
            case "array":
                if (preg_match("/.+(\[\])$/m", $expectedType)) {
                    $expectedPropertyType = str_ireplace("[]", "", $expectedType);
                    if (!$this->isSimpleType($expectedPropertyType)) {
                        $expectedPropertyType = "$namespace\\$expectedPropertyType";
                    }
                    foreach ($data AS $key => $value) {
                        if (!is_numeric($key)) {
                            $this->addMessage("No array of objects for $name");
                            $valid = false;
                            break;
                        }
                        $valid = $this->validateData($name, $namespace, $expectedPropertyType, $nullable, $value);
                        if ($valid === false) break;
                    }
                } else {
                    $this->addMessage("Unexpected data-type for $name (expected: $expectedType, actual: " . gettype($data) . ")");
                    $valid = false;
                }
                break;
            case "boolean":
            case "integer":
            case "string":
            case "double":
                $valid = $this->validate($name, $expectedType, $data);
                break;
            case "null":
                $valid = $nullable;
                if ($valid === false) {
                    $dataType = gettype($data);
                    $this->addMessage("Invalid data-type for $name (expected: $expectedType, actual: $dataType)");
                }
                break;
            case "object":
                $valid = $this->validate($name, $expectedType, $data);
                if ($valid === false) break;
                $objectName = get_class($data);
                $properties = array_keys(get_class_vars($objectName));
                foreach ($properties AS $propertyName) {
                    $scheme = $this->getScheme($objectName, $propertyName);
                    if ($scheme === false) return false;
                    $propertyType = str_ireplace("|null", "", $scheme[2]);
                    $expectedPropertyType = (class_exists("$namespace\\$propertyType") || array_key_exists("$namespace\\$propertyType", $this->arInspectedClasses)) ? "$namespace\\$propertyType" : $propertyType;
                    $nullable = $scheme[3];

                    // Validate properties
                    $valid = $this->validateData("$name-$propertyName", $namespace, $expectedPropertyType, $nullable, $data->$propertyName);
                    if ($valid === false) break;
                }
                break;
            default:
                $this->addMessage("Unsupported data-type for $name: " . gettype($data));
                $valid = false;
                break;
        }

        return $valid;
    }
}