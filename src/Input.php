<?php

namespace AtpCore;

class Input
{

    /**
     * Check if array (values in array) start with capital
     * @param array $data
     * @return array
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
     * Find (longest) match of strings
     *
     * @param string $string
     * @param string $otherString
     * @return string
     */
    public static function findMatch($string, $otherString)
    {
        // Initialize matched string
        $match = "";

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

        // Return
        return $match;
    }

    /**
     * Decode x-form-encoded data
     *
     * @param string $dataString example: test=1&test2=3&data=1+2
     * @param array|null $paramKeys parameter-keys
     * @return array|object
     */
    public static function formDecode($dataString, $paramKeys = null)
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
            $params = self::setInputParams($params, $paramKeys);
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
    public function isAssocArray($array)
    {
        if (empty($array)) return false;
        return (array_keys($array) !== range(0, count($array)-1));
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

    /**
     * Set parameters by input
     *
     * @param $params
     * @return \stdClass
     */
    public static function setInputParams($params, $paramKeys) {
        // Parse variables into params (set undefined indexes to null)
        $output = new \stdClass;
        foreach ($paramKeys AS $paramKey) {
            $output->{$paramKey} = $params[$paramKey] ?? null;
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
}