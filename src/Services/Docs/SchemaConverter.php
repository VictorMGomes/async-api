<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi\Services\Docs;

use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionProperty;
use Spatie\LaravelData\Data;
use Laravel\Surveyor\Types\Contracts\Type as SurveyorType;
use Laravel\Surveyor\Types\ClassType;
use Laravel\Surveyor\Types\ArrayType;
use Laravel\Surveyor\Types\ArrayShapeType;
use Laravel\Surveyor\Types\UnionType;
use Laravel\Surveyor\Types\IntType;
use Laravel\Surveyor\Types\FloatType;
use Laravel\Surveyor\Types\BoolType;
use Laravel\Surveyor\Types\NumberType;
use Laravel\Surveyor\Types\NullType;

class SchemaConverter
{
    private array $schemas = [];

    /**
     * Converte um tipo nativo do Surveyor em um JSON Schema válido.
     */
    public function convertSurveyorType(SurveyorType $type): array
    {
        if ($type instanceof ClassType) {
            return $this->convert($type->value, true);
        }

        if ($type instanceof ArrayType) {
            if ($type->isList()) {
                $itemsSchema = [];
                $valueType = $type->valueType();
                if ($valueType && ! $valueType instanceof \Laravel\Surveyor\Types\MixedType) {
                    $itemsSchema = $this->convertSurveyorType($valueType);
                }
                return [
                    'type' => 'array',
                    'items' => $itemsSchema,
                ];
            } else {
                $properties = [];
                foreach ($type->value as $key => $subType) {
                    $properties[$key] = $this->convertSurveyorType($subType);
                }
                return [
                    'type' => 'object',
                    'properties' => (object) $properties,
                ];
            }
        }

        if ($type instanceof ArrayShapeType) {
            return [
                'type' => 'object',
                'additionalProperties' => $this->convertSurveyorType($type->valueType),
            ];
        }

        if ($type instanceof UnionType) {
            $oneOf = [];
            foreach ($type->types as $subType) {
                if ($subType instanceof NullType) {
                    continue; // Lidamos com null separadamente
                }
                $oneOf[] = $this->convertSurveyorType($subType);
            }
            if (count($oneOf) === 1) {
                $schema = $oneOf[0];
                return $schema; // Em OpenAPI 3.0 não tem nullable fácil, vamos simplificar o schema
            }
            if (count($oneOf) > 1) {
                return ['oneOf' => $oneOf];
            }
        }

        if ($type instanceof IntType) return ['type' => 'integer'];
        if ($type instanceof FloatType || $type instanceof NumberType) return ['type' => 'number'];
        if ($type instanceof BoolType) return ['type' => 'boolean'];
        if ($type instanceof NullType) return ['type' => 'null'];
        
        return ['type' => 'string'];
    }

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
                /** @var \BackedEnum[] $cases */
                $cases = $typeName::cases();

                $schema = [
                    'type' => $backingTypeName === 'int' ? 'integer' : 'string',
                    'enum' => array_map(fn ($case) => $case->value, $cases),
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
            $currentType = $schema['type'] ?? 'string';

            if (isset($schema['$ref'])) {
                return [
                    'oneOf' => [
                        $schema,
                        ['type' => 'null'],
                    ],
                ];
            }

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
