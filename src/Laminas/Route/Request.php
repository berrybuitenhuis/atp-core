<?php

namespace AtpCore\Laminas\Route;

use AtpCore\Error;
use AtpCore\Input;
use Laminas\Http\Request as LaminasRequest;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Request
{
    private static ?object $serializer = null;


    /**
     * Initialize (custom) request-model from data
     *
     * @param mixed $data
     * @return static|Error|\stdClass
     */
    public static function fromData(mixed $data, bool $safe = true): static|Error|\stdClass
    {
        // Parse data into array
        if (is_string($data)) {
            parse_str($data, $requestData);
        } elseif (is_object($data)) {
            $requestData = get_object_vars($data);
        } elseif (is_array($data)) {
            $requestData = $data;
        } else {
            $type = gettype($data);
            return new Error(messages: ["Unsupported data-type provided for payload: $data (type: $type)"]);
        }

        // Return
        $result = self::deserialize($requestData);
        if (Error::isError($result)) {
            if ($safe === true) {
                trigger_error("Invalid request body: {$result->getData()->getMessage()}", E_USER_WARNING);
                return $data;
            } else {
                return new Error(messages: ["Invalid request body: {$result->getData()->getMessage()}"]);
            }
        } else {
            return $result;
        }
    }

    /**
     * Initialize (custom) request-model from HTTP-request
     *
     * @param LaminasRequest $request
     * @return static|Error|\stdClass
     */
    public static function fromHttpRequest(LaminasRequest $request, bool $safe = true): static|Error|\stdClass
    {
        $data = $request->getContent();
        if (Input::isJson($data)) {
            // Decode JSON-string
            $requestData = json_decode($data, true);
        } else {
            // Parse string into array
            parse_str($data, $requestData);
        }

        // Return
        $result = self::deserialize($requestData);
        if (Error::isError($result)) {
            if ($safe === true) {
                trigger_error("Invalid request body: {$result->getData()->getMessage()}", E_USER_WARNING);
                $reflectionClass = new \ReflectionClass(static::class);
                $properties = array_map(fn($p) => $p->getName(), $reflectionClass->getConstructor()?->getParameters() ?? []);
                return \AtpCore\Input::formDecode(json_encode($requestData), $properties);
            } else {
                return new Error(messages: ["Invalid request body: {$result->getData()->getMessage()}"]);
            }
        } else {
            return $result;
        }
    }

    /**
     * Convert request-object into array
     *
     * @param bool $excludeNulls
     * @return array
     */
    public function toArray(bool $excludeNulls = false): array
    {
        // Get array of object
        $data = get_object_vars($this);

        // Remove nullable values (if applicable)
        if ($excludeNulls === true) {
            $data = array_filter($data, static fn($v) => $v !== null);
        }

        // Return
        return $data;
    }

    private static function deserialize(array $data): \stdClass|Error
    {
        // Transform camel-case key-names (to snake-case key-names)
        $content = Input::toSnakeCaseKeyNames($data);

        // Deserialize request-model
        $serializer = self::getSerializer();
        try {
            return $serializer->deserialize(
                data: json_encode($content),
                type: static::class,
                format: 'json',
            );
        } catch (\Throwable $e) {
            return new Error(data: $e, messages: ["Invalid request body: {$e->getMessage()}"]);
        }
    }

    private static function getSerializer(): object
    {
        // Return serializer if already initiated
        if (self::$serializer !== null) {
            return self::$serializer;
        }

        // Initialize serializer
        $metadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $normalizer = new ObjectNormalizer(
            classMetadataFactory: $metadataFactory,
            nameConverter: new CamelCaseToSnakeCaseNameConverter(),
            defaultContext: [AbstractObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES => false]
        );
        self::$serializer = new Serializer(normalizers: [$normalizer], encoders: [new JsonEncoder()]);

        // Return
        return self::$serializer;
    }
}
