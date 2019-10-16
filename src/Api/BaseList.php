<?php /** @noinspection PhpUndefinedMethodInspection */

namespace AtpCore\Api;

use ArrayAccess;
use BadMethodCallException;
use Countable;
use DateTime;
use Exception;
use Iterator;
use ReflectionClass;
use ReflectionObject;
use stdClass;

abstract class BaseList implements BaseInterface, ArrayAccess, Iterator, Countable
{

    public $items = [];
    protected $position = 0;
    protected static $populateProperties;
    protected static $exportProperties;

    /**
     * BaseList constructor.
     * @param array $data
     * @throws Exception
     */
    public function __construct($data = [])
    {
        if (!empty($data)) {
            $this->populate($data);
        }
    }

    /**
     * Returns a JSON encoded string with current Entity.
     * We have filtered out the readOnly elements
     * @return string
     * @throws Exception
     */
    public function encode()
    {
        if (!isset(self::$exportProperties[get_called_class()])) {
            $reflectionObject = new ReflectionObject($this);

            foreach ($reflectionObject->getProperties() as $property) {
                if (strpos($property->getDocComment(), '@ReadOnly') === false) {
                    self::$exportProperties[get_called_class()][] = $property->getName();
                }
            }
        }

        return json_encode(
            array_intersect_key($this->formatVars(get_object_vars($this)), array_flip(self::$exportProperties[get_called_class()]))
        );
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
            if (is_object($var) && !($var instanceof stdClass)) {
                if ($var instanceof Base) {
                    $var = json_decode($var->encode());
                } elseif ($var instanceof DateTime) {
                    $var = $var->format(DateTime::ISO8601);
                } elseif (method_exists($var, '__toString')) {
                    $var = (string)$var;
                } else {
                    throw new Exception('Cannot convert object of type ' . get_class($var) . ' to string');
                }
            }
            $formattedVars[$varName] = $var;
        }
        return $formattedVars;
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
            if (property_exists($this, $property)) {
                return $this->$property;
            }
        }
        throw new BadMethodCallException('Unknown method ' . $name);
    }

    /**
     * @return string
     * @throws Exception
     */
    function __toString()
    {
        return $this->encode();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    protected function getItemType()
    {
        static $type;
        if (!$type) {
            $class = new ReflectionClass($this);

            if (!preg_match('/@return\s+([a-zA-Z\\\\]+)/s', $class->getDocComment(), $matches)) {
                throw new Exception('_item have no Type configured');
            }
            $type = $matches[1];
        }

        return $type;
    }

    /**
     * The return value will be casted to boolean if non-boolean was returned.
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * The offset to retrieve (xan return all value types)
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        $ref = new ReflectionClass(get_called_class());
        $offset = $offset ? : count($this->items);
        $className = $ref->getNamespaceName() . '\\' . $this->getItemType();

        $item = new $className;
        $this->items[$offset] = $item->populate($value);
    }

    /**
     * @param mixed $offset <p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * @param array $data
     */
    public function populate($data)
    {
        foreach ($data as $dataElement) {
            $this[] = $dataElement;
        }
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->items[$this->position];
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @return boolean
     */
    public function valid()
    {
        return isset($this->items[$this->position]);
    }

    public function next()
    {
        ++$this->position;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Gets a slice of elements
     * @param int $offset
     * @param int $number
     * @return array
     */
    public function slice($offset, $number = null)
    {
        $number = $number ?: $this->count();
        $elements = [];

        for ($i = $offset; $i < $number; $i++) {
            $elements[$i] = $this->offsetGet($i);
        }

        return $elements;
    }

    /**
     * @param callable $filter
     * @param boolean $returnSelf
     * @return array|static
     * @throws Exception
     */
    public function filter(callable $filter, $returnSelf = false)
    {
        $elements = array_filter($this->items, $filter);

        return $returnSelf ? new static($elements) : $elements;
    }
} 