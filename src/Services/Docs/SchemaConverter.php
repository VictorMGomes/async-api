<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi\Services\Docs;

use ReflectionClass;
use ReflectionEnum;
use Laravel\Surveyor\Analyzer\Analyzer;
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
            if (enum_exists($type->value)) {
                $enumReflection = new ReflectionEnum($type->value);

                if ($enumReflection->isBacked()) {
                    $backingType = $enumReflection->getBackingType();
                    $backingTypeName = $backingType?->getName() ?? 'string';
                    
                    return [
                        'type' => $backingTypeName === 'int' ? 'integer' : 'string',
                        'enum' => array_map(fn ($case) => $case->value, $type->value::cases()),
                    ];
                }

                return [
                    'type' => 'string',
                    'enum' => array_map(fn ($case) => $case->name, $type->value::cases()),
                ];
            }

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
        // Agora usamos o Analyzer do Surveyor para extrair as propriedades em vez do Reflection nativo
        $analyzer = app(Analyzer::class);
        $analyzed = $analyzer->analyzeClass($className)->result();
        
        $properties = [];

        foreach ($analyzed->publicProperties() as $prop) {
            $propName = $prop->name;
            $properties[$propName] = $this->convertSurveyorType($prop->type);
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
}
