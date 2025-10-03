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
     * Initialize (custom) request-model from HTTP-request
     *
     * @param LaminasRequest $request
     * @return static|Error|\stdClass
     */
    public static function fromHttpRequest(LaminasRequest $request, bool $safe = true): static|\AtpCore\Error|\stdClass
    {
        $data = $request->getContent();
        if (Input::isJson($data)) {
            // Decode JSON-string
            $originalContent = json_decode($data, true);
        } else {
            // Parse string into array
            parse_str($data, $originalContent);
        }

        // Transform camel-case key-names (to snake-case key-names)
        $content = Input::toSnakeCaseKeyNames($originalContent);

        // Deserialize request-model
        $serializer = self::getSerializer();
        try {
            return $serializer->deserialize(
                data: json_encode($content),
                type: static::class,
                format: 'json',
            );
        } catch (\Throwable $e) {
            if ($safe) {
                trigger_error("Invalid request body: {$e->getMessage()}", E_USER_WARNING);
                $reflectionClass = new \ReflectionClass(static::class);
                $properties = array_map(fn($p) => $p->getName(), $reflectionClass->getConstructor()?->getParameters() ?? []);
                return \AtpCore\Input::formDecode(json_encode($originalContent), $properties);
            } else {
                return new Error(messages: ["Invalid request body: {$e->getMessage()}"]);
            }
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
