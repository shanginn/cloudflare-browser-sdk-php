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
        $json = $this->serializer->serialize($data, 'json');
        
        // Fix: Cloudflare API expects camelCase for screenshotOptions
        if ($data instanceof Requests\ScreenshotRequest) {
            $json = str_replace('"screenshot_options":', '"screenshotOptions":', $json);
        }
        
        // Fix: Cloudflare API expects camelCase for pdfOptions
        if ($data instanceof Requests\PdfRequest) {
            $json = str_replace('"pdf_options":', '"pdfOptions":', $json);
            
            // Fix: Cloudflare API expects 'format' inside pdfOptions, not as top-level field
            // Move format from top-level into pdfOptions
            $decoded = json_decode($json, true);
            if (isset($decoded['format']) && $decoded['format'] !== null) {
                if (!isset($decoded['pdfOptions'])) {
                    $decoded['pdfOptions'] = [];
                }
                $decoded['pdfOptions']['format'] = $decoded['format'];
                unset($decoded['format']);
                $json = json_encode($decoded);
            }
        }
        
        return $json;
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
