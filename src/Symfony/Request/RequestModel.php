<?php

namespace AtpCore\Symfony\Request;

use AtpCore\Error;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Request-model: the input of a write-action, documented with #[OA\Schema] and #[OA\Property] on the promoted constructor-properties of a subclass
 *
 * Create: Model::fromRequest($request); update: Model::fromRequest($request, $currentObject), which starts from static::fromObject($currentObject) and lets the body overwrite it
 */
abstract class RequestModel
{
    private static ?Serializer $serializer = null;

    /**
     * Model from the request-body (JSON, form-POST or url-encoded)
     */
    public static function fromRequest(Request $request, ?object $currentObject = null): static|Error
    {
        return static::fromData(RequestBody::fromRequest($request), $currentObject);
    }

    /**
     * Model from data (keys in camelCase or snake_case); unknown keys, wrong types and missing required values give an Error
     */
    public static function fromData(array $data, ?object $currentObject = null): static|Error
    {
        // Update: start from the current object
        $context = [];
        if ($currentObject !== null) {
            if (!method_exists(static::class, 'fromObject')) {
                return new Error(messages: ["Method 'fromObject' not implemented for " . static::class]);
            }
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = static::fromObject($currentObject);
        }

        // Keys as snake_case, the name-converter maps them on the (camelCase) properties
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        $content = [];
        foreach ($data as $key => $value) {
            $content[$nameConverter->normalize((string) $key)] = $value;
        }

        // Return
        try {
            return self::getSerializer()->denormalize($content, static::class, 'json', $context);
        } catch (\Throwable $e) {
            return new Error(messages: ["Invalid request body: {$e->getMessage()}"]);
        }
    }

    /**
     * Data of the model as array (property => value), e.g. for the repository
     */
    public function toArray(bool $excludeNulls = false): array
    {
        $data = get_object_vars($this);

        // Remove nullable values (if applicable)
        if ($excludeNulls === true) {
            $data = array_filter($data, static fn ($value) => $value !== null);
        }

        // Return
        return $data;
    }

    private static function getSerializer(): Serializer
    {
        if (self::$serializer === null) {
            $normalizer = new ObjectNormalizer(
                classMetadataFactory: new ClassMetadataFactory(new AttributeLoader()),
                nameConverter: new CamelCaseToSnakeCaseNameConverter(),
                propertyTypeExtractor: new ReflectionExtractor(),
                defaultContext: [AbstractObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES => false],
            );
            self::$serializer = new Serializer(normalizers: [new DateTimeNormalizer(), $normalizer]);
        }

        // Return
        return self::$serializer;
    }
}
