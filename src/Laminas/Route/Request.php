<?php

namespace AtpCore\Laminas\Route;

use AtpCore\Error;
use AtpCore\Input;
use Laminas\Http\Request as LaminasRequest;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Request
{
    private Serializer $serializer;

    public function __construct()
    {
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
        $this->serializer = new Serializer([$normalizer], [new JsonEncoder()]);
    }

    /**
     * @template T
     * @param LaminasRequest $request
     * @param class-string<T> $requestClass
     * @return T|Error
     */
    public function toRequest(LaminasRequest $request, string $requestClass)
    {
        $data = $request->getContent();
        if (Input::isJson($data)) {
            // Decode JSON-string
            $content = json_decode($data, true);
        } else {
            // Parse string into array
            parse_str($data, $content);
        }

        // Transform camel-case key-names (to snake-case key-names)
        $content = Input::toSnakeCaseKeyNames($content);

        try {
            return $this->serializer->deserialize(
                data: json_encode($content),
                type: $requestClass,
                format: 'json',
            );
        } catch (\Throwable $e) {
            return new Error(messages: ["Invalid request body: " . $e->getMessage()]);
        }
    }
}
