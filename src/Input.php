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
     * Decode x-form-encoded data
     *
     * @param string $dataString, example test=1&test2=3&data=1+2
     * @return array
     */
    public static function formDecode($dataString)
    {
        if (empty($dataString)) return [];
        if (self::isJson($dataString)) return json_decode($dataString, true);

        // Parse string into variables
        parse_str($dataString, $output);

        return $output;
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
     * Set parameters by request-object
     *
     * @param $params
     * @return array
     */
    public static function setParams($params)
    {
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

        return $params;
    }

}