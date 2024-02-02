<?php

namespace AtpCore;

class Input
{

    /**
     * Check if array (values in array) start with capital
     * @param array $data
     * @return bool
     */
    public static function containsCapitalizedValue($data) {
        return !empty(array_filter($data, function($var) { return preg_match("/^[A-Z]{1}/", $var); }));
    }

    /**
     * Convert boolean into string/integer
     *
     * @param boolean $value
     * @param string $output
     * @return string|integer|null
     */
    public static function convertBoolean($value, $output = "string")
    {
        // Return empty value
        if ($value === null) return null;

        // Return integer-value of boolean
        if (strtolower($output) == "integer" || strtolower($output) == "int") {
            return $value ? 1 : 0;
        }

        // Return string-value of boolean
        return $value ? 'true' : 'false';
    }

    /**
     * Convert JSON-string (or object/array) into array/object
     *
     * @param $dataString
     * @param string $output
     * @return mixed
     */
    public function convertJson($dataString, $output = "object")
    {
        if (!is_object($dataString) && !is_array($dataString)) {
            $dataString = str_replace("'", '"', $dataString);
            if (self::isJson($dataString)) {
                $data = $dataString;
            } else {
                return null;
            }
        } else {
            $data = json_encode($dataString);
        }

        if ($output == "object") {
            return json_decode($data);
        } else {
            return json_decode($data, true);
        }
    }

    /**
     * Convert XML-object (SimpleXMLElement) into object
     *
     * @param \SimpleXMLElement $data
     * @return object
     */
    public static function convertXML($data)
    {
        // Iterate data
        $data = self::convertXMLData($data);
        foreach (get_object_vars($data) AS $key => $value) {
            // Replace empty object into null
            if ($value instanceOf \stdClass && empty((array) $value)) $data->$key = null;
            // Replace string false into boolean
            elseif (is_string($value) && strtolower($value) === "false") $data->$key = false;
            // Replace string true into boolean
            elseif (is_string($value) && strtolower($value) === "true") $data->$key = true;
            // Replace empty string into null
            elseif (is_string($value) && empty($value)) $data->$key = null;
        }

        // Return
        return $data;
    }

    /**
     * Find (longest) match of strings
     *
     * @param string $string
     * @param string $otherString
     * @param boolean $completeWords
     * @return string
     */
    public static function findMatch($string, $otherString, $completeWords = false)
    {
        // Initialize matched string
        $match = "";

        if ($completeWords === true) {
            // Split (source) string into array for words
            $words = explode(" ", $string);

            // Iterate every word
            foreach ($words AS $word) {
                // Check if "new" matched string in other-string (add word to matched-string), else stop/return matched-string
                if(stripos($otherString, $match . $word . " ") === 0 || $otherString == $match . $word) {
                    $match .= $word . " ";
                } else {
                    $match = trim($match);
                    break;
                }
            }
        } else {
            // Split (source) string into array for every character
            $chars = str_split($string);

            // Iterate every character
            foreach ($chars as $char) {
                // Check if "new" matched string in other-string (add character to matched-string), else stop/return matched-string
                if(stripos($otherString, $match . $char) === 0) {
                    $match .= $char;
                } else {
                    break;
                }
            }
        }

        // Return
        return $match;
    }

    /**
     * Decode x-form-encoded data
     *
     * @param string $dataString example: test=1&test2=3&data=1+2
     * @param array|null $paramKeys parameter-keys
     * @param boolean $addIfNotExists parameter-keys
     * @return array|object
     */
    public static function formDecode($dataString, $paramKeys = null, $addIfNotExists = true)
    {
        if (empty($dataString)) {
            // Set empty parameter-list
            $params = [];
        } elseif (self::isJson($dataString)) {
            // Decode JSON-string
            $params = json_decode($dataString, true);
        } else {
            // Parse string into variables
            parse_str($dataString, $params);
        }

        // Check if default parameter-keys available (set undefined keys to null)
        if (!empty($paramKeys)) {
            $params = self::setInputParams($params, $paramKeys, $addIfNotExists);
        }

        // Return
        return $params;
    }

    /**
     * Get items (values in array) which contain specific string
     * @param string $searchString
     * @param array $data
     * @return array
     */
    public static function getItemsContainingString($searchString, $data) {
        return array_filter($data, function($var) use ($searchString) { return preg_match("/\b$searchString\b/i", $var); });
    }

    /**
     * Check if array is associative-array (or sequential)
     *
     * @param array $array
     * @return boolean
     */
    public static function isAssocArray($array)
    {
        if (empty($array)) return false;
        return (array_keys($array) !== range(0, count($array)-1));
    }

    /**
     * Check if value is empty (false/0 results in non-empty)
     *
     * @param mixed $value
     * @return boolean
     */
    public static function isEmpty($value)
    {
        // Initialize output
        $output = false;

        // Verify if value is empty
        if (is_array($value)) $output = empty($value);
        elseif (is_object($value)) $output = empty($value);
        elseif (is_bool($value)) $output = false;
        elseif ($value === null) $output = true;
        elseif (trim($value) == "") $output = true;

        // Return
        return $output;
    }

    /**
     * Check if value is NOT empty (false/0 results in non-empty)
     *
     * @param mixed $value
     * @return boolean
     */
    public static function isNotEmpty($value)
    {
        // Initialize output
        $empty = self::isEmpty($value);

        // Return
        return !($empty === true);
    }

    public static function isInteger($value)
    {
        // Initialize output
        $output = false;

        // Verify if value is "integer"
        if (is_int($value)) $output = true;
        elseif (is_array($value)) $output = false;
        elseif (is_object($value)) $output = false;
        elseif (is_bool($value)) $output = false;
        elseif ($value === null) $output = true;
        elseif (trim($value) == "") $output = true;
        elseif (is_string($value) && preg_match("/^(0|([1-9])[0-9]*)$/", $value)) $output = true;

        // Return
        return $output;
    }

    /**
     * Check if string is JSON-string
     *
     * @param string $dataString
     * @return boolean
     */
    public static function isJson($dataString)
    {
        return is_string($dataString) && is_array(json_decode($dataString, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    /**
     * Check if string is XML-string
     *
     * @param string $dataString
     * @return boolean
     */
    public static function isXml($dataString)
    {
        if (!is_string($dataString)) return false;

        $currentValue = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($dataString);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($currentValue);

        return $doc !== false && empty($errors);
    }

    /**
     * Re-index array-collection by property-name (instead of numeric sequence)
     *
     * @param array $arrayCollection
     * @param string $propertyName
     * @return array
     */
    public static function reindexArrayCollection($arrayCollection, $propertyName) {
        $output = [];

        if (count($arrayCollection) > 0) {
            $properties = explode("-", $propertyName);

            foreach ($arrayCollection as $item) {
                $index = $item;
                for ($i=0; $i < count($properties); $i++) {
                    $index = $index[$properties[$i]];
                }

                $output[$index] = $item;
            }
        }

        return $output;
    }

    /**
     * Re-index object-collection by property-name (instead of numeric sequence)
     *
     * @param array $objectCollection
     * @param string $propertyName
     * @return array
     */
    public static function reindexObjectCollection($objectCollection, $propertyName) {
        $output = [];

        if (count($objectCollection) > 0) {
            $properties = explode("-", $propertyName);

            foreach ($objectCollection as $object) {
                $index = $object;
                for ($i=0; $i < count($properties); $i++) {
                    $func = 'get' . ucfirst($properties[$i]);
                    $index = $index->$func();
                }

                $output[$index] = $object;
            }
        }

        return $output;
    }

    public static function removeNullableValues(array $data)
    {
        return array_filter($data, fn($value) => !is_null($value));
    }

    /**
     * Set parameters by input
     *
     * @param array $params
     * @param array $params
     * @param boolean $addIfNotExists
     * @return \stdClass
     */
    public static function setInputParams($params, $paramKeys, $addIfNotExists) {
        // Parse variables into params (set undefined indexes to null if $addIfNotExists is provided)
        $output = new \stdClass;
        foreach ($paramKeys AS $paramKey) {
            if ($addIfNotExists === true) {
                $output->{$paramKey} = $params[$paramKey] ?? null;
            } elseif (array_key_exists($paramKey, $params)) {
                $output->{$paramKey} = $params[$paramKey];
            }
        }

        // Return
        return $output;
    }

    /**
     * Set parameters by request-object
     *
     * @param $params
     * @return \stdClass
     */
    public static function setParams($params)
    {
        // Set parameters
        $params->fields = (isset($params->fields)) ? json_decode($params->fields, true) : null;
        $params->defaultFilter = (isset($params->defaultFilter)) ? json_decode($params->defaultFilter, true) : null;
        $params->filter = (isset($params->filter)) ? json_decode($params->filter, true) : null;
        $params->groupBy = (isset($params->groupBy)) ? json_decode($params->groupBy, true) : null;
        $params->having = (isset($params->having)) ? json_decode($params->having, true) : null;
        $params->orderBy = (isset($params->orderBy)) ? json_decode($params->orderBy, true) : null;
        $params->limit = (isset($params->limit)) ? (int) $params->limit : null;
        $params->offset = (isset($params->page) && (int) $params->page > 1) ? ((int) $params->page - 1) * $params->limit : null;
        $params->debug = (isset($params->debug) && ($params->debug == 'true' || $params->debug == 1)) ? true : false;
        $params->customRequestId = (isset($params->customRequestId) && !empty($params->customRequestId)) ? $params->customRequestId : null;

        // Return
        return $params;
    }

    /**
     * Sort an array by keys with customized order
     *
     * @param array $array
     * @param array $order
     * @return array
     */
    public static function sortArrayByKey($array, $order)
    {
        if (!is_array($array)) return $array;

        uksort($array, function($a, $b) use($order) {
            foreach($order as $value){
                if ($a == $value) return 0;
                if ($b == $value) return 1;
            }
        });

        return $array;
    }

    public static function stripSlashes($var)
    {
        // Avoid converting nullable-values into empty-string ("") with stripslashes
        if ($var === null) {
            return null;
        }

        // Avoid converting integer-value into string-value with stripslashes
        if (is_int($var)) {
            return $var;
        }

        // Apply function on uni/multidimensional array
        if (is_array($var)) {
            return array_map([__CLASS__, 'stripSlashes'], $var);
        }

        // Return stripslashed-value of $var
        return stripslashes($var);
    }

    /**
     * Convert SimpleXMLElement-object into object
     *
     * @param \SimpleXMLElement|object|array $data
     * @param bool $forceArray
     * @return object
     */
    private static function convertXMLData($data, $forceArray = false)
    {
        $output = new \stdClass();
        $values = (is_object($data)) ? get_object_vars($data) : $data;
        foreach ($values AS $key => $value) {
            if (is_array($value)) {
                // If element is not associative array (but sequential) avoid numeric element-keys (but respect sequential array)
                if (self::isAssocArray($value) === false) {
                    $output->$key = [];
                    foreach ($value AS $v) {
                        if (is_object($v) || is_array($v)) {
                            $output->$key[] = self::convertXMLData($v);
                        } else {
                            $output->$key[] = self::convertXMLValue($v);
                        }
                    }
                } else {
                    $output->$key = self::convertXMLData($value);
                }
            } elseif (is_object($value) && !empty($value)) {
                // If element has only 1 property and this is not an array -> force it
                if (count((array) $value) == 1) {
                    // Force property to be an array for only 1 value
                    $properties = array_keys(get_object_vars($value));
                    if (count((array) $value->{$properties[0]}) == 1) $forceArray = true;

                    // Force element to be an array if converted value is an object
                    $convertedValue = self::convertXMLData($value, $forceArray);
                    $convertedValueProperty = $convertedValue->{$properties[0]};
                    if (gettype($convertedValueProperty) == "object") {
                        $convertedValue = new \stdClass();
                        $convertedValue->{$properties[0]} = [$convertedValueProperty];
                    }
                    $output->$key = $convertedValue;
                } else {
                    $output->$key = self::convertXMLData($value);
                }
            }
            else {
                if (preg_match("/^[0-9]*$/i", $key) && $key == intval($key)) {
                    if (is_object($output) && empty((array) $output)) $output = [];
                    $output[] = self::convertXMLValue($value);
                } elseif ($forceArray === true) {
                    $output->$key = [self::convertXMLValue($value)];
                } else {
                    $output->$key = self::convertXMLValue($value);
                }
            }

            // Check for value-attributes (only for SimpleXMLElement-object)
            if (gettype($data->$key) === 'object' && get_class($data->key) === 'SimpleXMLElement') {
                $attributes = $data->$key->attributes();
                if (!empty($attributes)) {
                    foreach ($attributes as $k => $v) {
                        $output->{$key . "_" . $k} = self::convertXMLValue($v);
                    }
                }
            }
        }

        // Return
        return $output;
    }

    /**
     * Convert SimpleXMLElement-value into primitive
     *
     * @param \SimpleXMLElement $value
     * @return int|string
     */
    private static function convertXMLValue($value)
    {
        // Check for null-value or empty object
        if ($value === null) return $value;
        elseif (is_object($value) && empty($value)) return null;

        $stringValue = (string) $value;
        $intValue = (int) $value;
        if ($stringValue === (string) $intValue && strlen($stringValue) == strlen($intValue)) {
            return $intValue;
        }
        return $stringValue;
    }
}