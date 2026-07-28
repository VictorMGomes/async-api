<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi\Services\Docs;

use BackedEnum;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionProperty;
use Spatie\LaravelData\Data;

class SchemaConverter
{
    private array $schemas = [];

    /**
     * Converte uma classe DTO em um array de propriedades JSON Schema.
     */
    public function convert(string $className, bool $asRef = false): array
    {
        if (! class_exists($className)) {
            return [];
        }

        $shortName = (new ReflectionClass($className))->getShortName();

        if ($asRef) {
            if (! isset($this->schemas[$shortName])) {
                // Importante: extrair propriedades ANTES de registrar para evitar recursão infinita parcial
                $this->schemas[$shortName] = ['type' => 'object']; // Placeholder
                $this->schemas[$shortName] = $this->extractProperties($className);
            }

            return ['$ref' => "#/components/schemas/$shortName"];
        }

        return $this->extractProperties($className);
    }

    private function extractProperties(string $className): array
    {
        $reflection = new ReflectionClass($className);
        $properties = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $propName = $prop->getName();
            $properties[$propName] = $this->resolveType($prop);
        }

        return [
            'type' => 'object',
            'properties' => ! empty($properties) ? $properties : (object) [],
        ];
    }

    public function getSchemas(): array
    {
        return $this->schemas;
    }

    private function resolveType(ReflectionProperty $prop): array
    {
        $type = $prop->getType();

        if (! $type instanceof ReflectionNamedType) {
            return ['type' => 'string'];
        }

        $typeName = $type->getName();
        $isNullable = $type->allowsNull();
        $schema = [];

        if (enum_exists($typeName)) {
            $enumReflection = new ReflectionEnum($typeName);

            if ($enumReflection->isBacked()) {
                $backingType = $enumReflection->getBackingType();
                $backingTypeName = $backingType?->getName() ?? 'string';

                $schema = [
                    'type' => $backingTypeName === 'int' ? 'integer' : 'string',
                    'enum' => array_map(fn (BackedEnum $case) => $case->value, $typeName::cases()),
                ];
            } else {
                $schema = [
                    'type' => 'string',
                    'enum' => array_map(fn ($case) => $case->name, $typeName::cases()),
                ];
            }
        } elseif (is_subclass_of($typeName, Data::class) || (class_exists($typeName) && ! (new ReflectionClass($typeName))->isInternal())) {
            // Se for uma classe não interna, converte como referência direta (sem o wrapping extra)
            $schema = $this->convert($typeName, true);
        } else {
            $schema['type'] = match ($typeName) {
                'int' => 'integer',
                'float' => 'number',
                'bool' => 'boolean',
                'array' => 'array',
                default => 'string',
            };
        }

        if ($isNullable) {
            // Para AsyncAPI 3.0 / JSON Schema Draft 7, se o parser for rígido com o array de tipos:
            $currentType = $schema['type'] ?? 'string';

            // Se já for uma referência, usa oneOf
            if (isset($schema['$ref'])) {
                return [
                    'oneOf' => [
                        $schema,
                        ['type' => 'null'],
                    ],
                ];
            }

            // Para tipos simples, alguns parsers preferem a lista, outros oneOf.
            // Como o erro indicou que 'type' deve ser um dos valores permitidos (singular), vamos usar oneOf.
            return [
                'oneOf' => [
                    $schema,
                    ['type' => 'null'],
                ],
            ];
        }

        return $schema;
    }
}
