<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi\Services\Docs;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Spatie\LaravelData\Data;
use stdClass;
use Throwable;
use Victormgomes\AsyncApi\Attributes\AsyncApi;
use Laravel\Ranger\Ranger;
use Laravel\Surveyor\Analyzer\Analyzer;
use Laravel\Surveyor\Types\ArrayType;
use Laravel\Surveyor\Types\ClassType;
use Laravel\Surveyor\Types\StringType;

class AsyncApiGenerator
{
    public function __construct(
        protected SchemaConverter $schemaConverter
    ) {}

    public function generate(): array
    {
        $this->log('🚀 INICIANDO GERAÇÃO (GLOBAL CONFIG MODE)...');

        $info = config('asyncapi.info', []);

        $info['title'] = $info['title'] ?? config('app.name').' Broadcasting API';
        $info['version'] = $info['version'] ?? '1.0.0';

        $structure = [
            'asyncapi' => config('asyncapi.asyncapi_version', '3.0.0'),
            'info' => array_filter($info),
            'defaultContentType' => config('asyncapi.default_content_type', 'application/json'),
            'servers' => config('asyncapi.servers', []),
            'channels' => [],
            'operations' => [],
            'components' => [
                'schemas' => [],
                'securitySchemes' => config('asyncapi.components.securitySchemes', []),
            ],
        ];

        $paths = array_filter([app_path(), base_path('Modules'), base_path('modules')], 'is_dir');
        
        $this->log('🔍 Varrendo a aplicação com Ranger nos diretórios: '.implode(', ', $paths));

        $ranger = app(Ranger::class);
        $ranger->setAppPaths(...$paths);

        $analyzer = app(Analyzer::class);
        
        $validClassesCount = 0;

        $ranger->onBroadcastEvents(function (\Laravel\Ranger\Components\BroadcastEvent $event) use (&$structure, $analyzer, &$validClassesCount) {
            $this->log("💎 EVENTO BROADCAST ENCONTRADO (Ranger): {$event->className}");
            $processed = $this->processEvent($event, $analyzer, $structure);
            if ($processed) {
                $validClassesCount++;
            }
        });

        $ranger->walk();

        $this->log('✅ SCAN FINALIZADO. Classes válidas processadas: '.$validClassesCount);

        // Adiciona schemas reutilizáveis coletados durante o processamento
        $structure['components']['schemas'] = $this->schemaConverter->getSchemas();

        if (empty($structure['channels'])) {
            $structure['channels'] = new stdClass;
        }
        if (empty($structure['operations'])) {
            $structure['operations'] = new stdClass;
        }
        if (empty($structure['components']['schemas'])) {
            unset($structure['components']['schemas']);
        }
        if (empty($structure['components']['securitySchemes'])) {
            unset($structure['components']['securitySchemes']);
        }
        if (empty($structure['components'])) {
            unset($structure['components']);
        }
        if (isset($structure['servers']) && empty($structure['servers'])) {
            $structure['servers'] = new stdClass;
        }

        return $structure;
    }

    private function processEvent(\Laravel\Ranger\Components\BroadcastEvent $event, Analyzer $analyzer, array &$structure): bool
    {
        $className = $event->className;

        try {
            $reflection = new ReflectionClass($className);
            $attributes = $reflection->getAttributes(AsyncApi::class);

            if (empty($attributes)) {
                return false;
            }

            $attr = $attributes[0]->newInstance();

            $this->log("📝 Processando Classe: $className");

            $dtoClass = $attr->dto;
            if (! $dtoClass) {
                $this->log('   ❓ DTO não definido. Inspecionando construtor para encontrar o objeto de payload...');

                if ($reflection->hasMethod('__construct')) {
                    $params = $reflection->getMethod('__construct')->getParameters();
                    $potentialPayloads = [];

                    foreach ($params as $param) {
                        $type = $param->getType();
                        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                            $typeName = $type->getName();

                            if ($param->getName() === 'data') {
                                $dtoClass = $typeName;
                                break;
                            }

                            if (is_subclass_of($typeName, Data::class)) {
                                $potentialPayloads[] = $typeName;

                                continue;
                            }

                            if (str_contains($typeName, '\\DTOs\\') || str_ends_with($typeName, 'DTO')) {
                                $potentialPayloads[] = $typeName;
                            }
                        }
                    }

                    if (! $dtoClass && ! empty($potentialPayloads)) {
                        $dtoClass = $potentialPayloads[0];
                    }
                }

                if (! $dtoClass) {
                    $shortName = $reflection->getShortName();
                    $possibleGuesses = [
                        str_replace(['\\Events\\', $shortName], ['\\DTOs\\', $shortName.'EventDTO'], $className),
                        str_replace(['\\Events\\', $shortName], ['\\DTOs\\', $shortName.'DTO'], $className),
                    ];

                    foreach ($possibleGuesses as $guessed) {
                        if (class_exists($guessed)) {
                            $dtoClass = $guessed;
                            break;
                        }
                    }
                }
            }

            if (! $dtoClass || ! class_exists($dtoClass)) {
                $this->log("   ⚠️ DTO não encontrado para $className. Usando a própria classe do evento como payload.");
                $dtoClass = $className;
            } else {
                $this->log("   ✅ DTO Encontrado: $dtoClass");
            }

            $channelUri = $attr->channel;
            if (! $channelUri) {
                $channelUri = $this->inferChannelFromSurveyor($className, $analyzer);
            }

            if (! $channelUri) {
                $this->log('   ❌ ERRO: Não foi possível ler o canal automaticamente.');

                return false;
            }

            // Normaliza as variáveis PHP injetadas no channel URI para o formato AsyncAPI {param}
            $channelUri = preg_replace_callback('/\{\$(?:this->)?(?:[a-zA-Z0-9_]+->)*([a-zA-Z0-9_]+)\}/', function ($m) {
                return '{'.$m[1].'}';
            }, $channelUri);

            $channelUri = preg_replace_callback('/\$(?:this->)?(?:[a-zA-Z0-9_]+->)*([a-zA-Z0-9_]+)/', function ($m) {
                return '{'.$m[1].'}';
            }, $channelUri);

            $eventName = $attr->name ?? $this->safelyGetBroadcastAs($reflection);

            $payloadSchema = $this->schemaConverter->convert($dtoClass, true);

            $channelKey = str_replace(['{', '}', '.', '/'], '_', $channelUri);
            if (! isset($structure['channels'][$channelKey])) {
                $structure['channels'][$channelKey] = [
                    'address' => $channelUri,
                    'messages' => [],
                ];

                if (preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $channelUri, $matches)) {
                    $parameters = [];
                    foreach ($matches[1] as $paramName) {
                        $parameters[$paramName] = [
                            'description' => "Parâmetro dinâmico: $paramName",
                        ];
                    }
                    $structure['channels'][$channelKey]['parameters'] = $parameters;
                }
            }

            $messageKey = $eventName.'Message';
            $message = array_filter([
                'name' => $eventName,
                'title' => $eventName,
                'summary' => $attr->summary,
                'description' => $attr->description,
                'payload' => $payloadSchema,
                'correlationId' => $attr->correlationId ? ['location' => $attr->correlationId] : null,
                'tags' => ! empty($attr->tags) ? array_map(fn ($t) => ['name' => $t], $attr->tags) : null,
                'examples' => ! empty($attr->examples) ? $attr->examples : null,
                'bindings' => ! empty($attr->bindings) ? $attr->bindings : null,
                'externalDocs' => $attr->externalDocs,
            ]);

            $structure['channels'][$channelKey]['messages'][$messageKey] = $message;

            $security = null;
            if ($attr->security !== null) {
                $security = [];
                foreach ($attr->security as $s) {
                    if (is_string($s)) {
                        $security[] = ['$ref' => "#/components/securitySchemes/$s"];
                    } else {
                        $security[] = $s;
                    }
                }
            }

            $operationId = $attr->operationId ?? $attr->action.$eventName;
            $structure['operations'][$operationId] = array_filter([
                'action' => $attr->action,
                'channel' => ['$ref' => "#/channels/$channelKey"],
                'summary' => $attr->summary ?? "Operation for $eventName",
                'security' => $security,
                'messages' => [
                    ['$ref' => "#/channels/$channelKey/messages/$messageKey"],
                ],
            ]);

            $this->log("   ✨ Sucesso! Adicionado ao canal: $channelUri");
            
            return true;

        } catch (Throwable $e) {
            $this->log("   💀 EXCEPTION em $className: ".$e->getMessage());
            return false;
        }
    }

    private function inferChannelFromSurveyor(string $className, Analyzer $analyzer): ?string
    {
        try {
            $analyzed = $analyzer->analyzeClass($className)->result();
            if (! $analyzed->hasMethod('broadcastOn')) {
                return null;
            }
            
            $returnType = $analyzed->getMethod('broadcastOn')->returnType();
            
            return $this->extractChannelUriFromType($returnType);
        } catch (Throwable $e) {
            $this->log("   💀 ERRO AO INFERIR CANAL via Surveyor: ".$e->getMessage());
            return null;
        }
    }

    private function extractChannelUriFromType(mixed $type): ?string
    {
        if ($type instanceof ClassType) {
            $prop = new ReflectionProperty(ClassType::class, 'constructorArguments');
            $prop->setAccessible(true);
            $args = $prop->getValue($type);
            
            if (is_array($args) && count($args) > 0 && $args[0] instanceof StringType) {
                return $args[0]->value;
            }
        }
        
        if ($type instanceof ArrayType) {
            if (! empty($type->value)) {
                // Para manter a compatibilidade original que esperava uma string de URI, 
                // retornamos a primeira URI válida encontrada no array.
                foreach ($type->value as $item) {
                    $channel = $this->extractChannelUriFromType($item);
                    if ($channel) {
                        return $channel;
                    }
                }
            }
        }
        
        return null;
    }

    private function safelyGetBroadcastAs(ReflectionClass $reflection): string
    {
        try {
            if ($reflection->hasMethod('broadcastAs')) {
                return $reflection->newInstanceWithoutConstructor()->broadcastAs();
            }
        } catch (Throwable $e) {
        }

        return $reflection->getShortName();
    }

    private function log(string $message): void
    {
        if (config('async-api.debug', true)) {
            Log::info("[AsyncApi] $message");
        }
    }
}
