<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser;

use Crell\Serde\SerdeCommon;
use Shanginn\CloudflareBrowser\Exceptions\CloudflareDeserializeException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Spiral\JsonSchemaGenerator\Generator;

class CloudflareSerializer
{
    private SerdeCommon $deserializer;
    private SerializerInterface $serializer;
    private Generator $schemaGenerator;

    public function __construct()
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [
            new BackedEnumNormalizer(),
            new ObjectNormalizer(
                nameConverter: new CamelCaseToSnakeCaseNameConverter(),
                defaultContext: [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]
            ),
        ];

        $this->serializer = new Serializer($normalizers, $encoders);
        $this->deserializer = new SerdeCommon();
        $this->schemaGenerator = new Generator();
    }

    public function serialize(mixed $data): string
    {
        return $this->serializer->serialize($data, 'json');
    }

    public function deserialize(mixed $serialized, string $to): object
    {
        try {
            return $this->deserializer->deserialize($serialized, 'json', $to);
        } catch (\Throwable $e) {
            throw new CloudflareDeserializeException($serialized, $to, $e);
        }
    }
    
    public function generateSchema(string $class): array
    {
        // Simple integration to generate schema array from class for /json endpoint
        return json_decode(json_encode($this->schemaGenerator->generate($class)), true);
    }
}
