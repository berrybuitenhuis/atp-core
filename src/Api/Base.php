<?php /** @noinspection PhpUndefinedMethodInspection */

namespace AtpCore\Api;

use BadMethodCallException;
use DateTime;
use Exception;
use InvalidArgumentException;
use ReflectionObject;
use ReflectionProperty;
use stdClass;
use AtpCore\Input;

abstract class Base implements BaseInterface
{

    protected static $populateProperties;
    protected static $exportProperties;

    /**
     * Base constructor.
     * @param array $data
     * @throws Exception
     */
    public function __construct($data = [])
    {
        if (!empty($data)) {
            $this->populate((object)$data);
        }
    }

    /**
     * Returns a JSON encoded string with current Entity.
     * We have filtered out the readOnly elements
     * @param bool $removeNullableValues
     * @return string
     * @throws Exception
     */
    public function encode($removeNullableValues = false)
    {
        if (!isset(self::$exportProperties[get_called_class()])) {
            $reflectionObject = new ReflectionObject($this);

            foreach ($reflectionObject->getProperties() as $property) {
                if (stripos($property->getDocComment(), '@ReadOnly') === false) {
                    self::$exportProperties[get_called_class()][] = $property->getName();
                }
            }
        }

        $data = array_intersect_key($this->formatVars(get_object_vars($this)), array_flip(self::$exportProperties[get_called_class()]));
        if ($removeNullableValues) $data  = Input::removeNullableValues($data);
        return json_encode($data);
    }

    /**
     * Parse vars so that it can be property json encoded
     * @param array $vars
     * @return array parsed vars
     * @throws Exception
     */
    protected function formatVars(array $vars)
    {
        $formattedVars = [];
        foreach ($vars as $varName => $var) {
            if (is_object($var) && !($var instanceof stdClass) ) {
                if ($var instanceof Base) {
                    $var = $var->encode();
                    if (Input::isJson($var)) {
                        $var = json_decode($var);
                    }
                } elseif ($var instanceof DateTime) {
                    $var = $var->format(DateTime::ISO8601);
                } elseif (method_exists($var, '__toString')) {
                    $var = (string)$var;
                } else {
                    throw new \Exception('Cannot convert object of type ' . get_class($var) . ' to string');
                }
            }
            $formattedVars[$varName] = $var;
        }

        return $formattedVars;
    }

    /**
     * Loop over all properties and set them in the entity
     * @param \stdClass $data
     * @return self
     * @throws Exception
     */
    public function populate($data)
    {
        if ($data === null || empty($data)) {
            return $this;
        }

        if (!($data instanceof \stdClass)) {
            throw new InvalidArgumentException('$data should be instance of stdClass');
        }

        if (!isset(static::$populateProperties[get_called_class()])) {
            $reflectionObject = new \ReflectionObject($this);

            // loop over all properties looking for custom data-types
            foreach ($reflectionObject->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $matches = [];
                // look for @var Class-name
                if (preg_match('/@var\s+([a-zA-Z\\\\]+)(\[])?/s', $property->getDocComment(), $matches) ) {
                    // found type hint

                    // look for basic type
                    if (in_array($matches[1], Defaults::$basicTypes)) {
                        static::$populateProperties[get_called_class()][$property->getName()] = $matches[1];
                        continue;
                    }

                    // found a custom data type
                    $className = substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__, '\\')) . '\\' . $matches[1];
                    if (!class_exists($className)) {
                        throw new \Exception('Could not find type:' . $className);
                    }
                    if (!is_subclass_of($className, '\AtpCore\Api\BaseInterface')) {
                        throw new \Exception('Type is not known:' . $className);
                    }
                    static::$populateProperties[get_called_class()][$property->getName()] = $className;
                } else {
                    static::$populateProperties[get_called_class()][$property->getName()] = 'string';
                }
            }
        }

        // Set values inside entity and populate if custom entity
        foreach (get_object_vars($data) as $name => $value) {
            // only set properties that exist (are public)
            if (isset(self::$populateProperties[get_called_class()][$name])) {
                $type = self::$populateProperties[get_called_class()][$name];

                // if default type, parse it to that type (unless stdClass)
                if (in_array($type, Defaults::$basicTypes)) {
                    if (!( $value instanceof \stdClass) ) {
                        if (!is_null($value)) {
                            settype($value, $type);
                        }
                    }
                    $this->$name = $value;
                } else {
                    // This is a custom value. If a list, we loop over each item
                    $typeObject = new $type;
                    if (is_array($value)) {
                        $this->$name = [];
                        foreach ($value as $keyElement => $valueElement) {
                            array_push(
                                $this->$name,
                                $typeObject->populate($valueElement)
                            );
                        }
                    } else {
                        // a single item. Create new data-type and populate
                        $this->$name = $typeObject->populate($value);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param string $name
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function __call($name, $params)
    {
        $matches = [];
        if (preg_match('/^get(\w+)/', $name, $matches)) {
            $property = lcfirst($matches[1]);
            if (property_exists($this, $property) ) {
                return $this->$property;
            }
        }
        throw new BadMethodCallException('Unknown method ' . $name);
    }

    /**
     * Merge current data with provided array
     * @param array $data
     * @throws Exception
     */
    public function merge(array $data)
    {
        $this->populate((object)$data);
    }

    /**
     * @return string
     * @throws Exception
     */
    function __toString()
    {
        return $this->encode();
    }

} 