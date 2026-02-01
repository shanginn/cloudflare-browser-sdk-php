<?php

declare(strict_types=1);

namespace Tests;

use Spiral\JsonSchemaGenerator\Attribute\Field;

class SampleJsonSchema
{
    public function __construct(
        #[Field(title: 'Product Name')]
        public string $name,

        #[Field(title: 'Price')]
        public float $price
    ) {}
}
