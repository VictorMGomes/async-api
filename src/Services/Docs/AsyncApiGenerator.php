<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi\Services\Docs;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionNamedType;
use Spatie\LaravelData\Data;
use stdClass;
use Symfony\Component\Finder\Finder;
use Throwable;
use Victormgomes\AsyncApi\Attributes\AsyncApi;

class AsyncApiGenerator
{
    public function __construct(
        protected SchemaConverter $schemaConverter
    ) {}

    public function generate(): array
    {
        $this->log('🚀 INICIANDO GERAÇÃO (GLOBAL CONFIG MODE)...');

        $info = config('asyncapi.info', []);

        $info['title'] = $info['title'] ?? config('app.name').' WebSocket API';
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

        $classes = $this->scanClasses();

        $this->log('✅ SCAN FINALIZADO. Classes válidas encontradas: '.count($classes));

        foreach ($classes as $class) {
            $this->processClass($class, $structure);
        }

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

    private function processClass(string $className, array &$structure): void
    {
        try {
            $reflection = new ReflectionClass($className);
            $attributes = $reflection->getAttributes(AsyncApi::class);

            if (empty($attributes)) {
                return;
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
                $channelUri = $this->inferChannelFromCode($reflection);
            }

            if (! $channelUri) {
                $this->log('   ❌ ERRO: Não foi possível ler o canal automaticamente.');

                return;
            }

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

        } catch (Throwable $e) {
            $this->log("   💀 EXCEPTION em $className: ".$e->getMessage());
        }
    }

    private function inferChannelFromCode(ReflectionClass $reflection): ?string
    {
        $fileName = $reflection->getFileName();
        if (! $fileName) {
            return null;
        }

        $content = file_get_contents($fileName);
        $pattern = '/new\s+(?:Private|Presence|Channel)?Channel\s*\(\s*["\']([^"\']+)["\']\s*\)/';

        if (preg_match($pattern, $content, $matches)) {
            $rawChannel = $matches[1];

            $cleanChannel = preg_replace_callback('/\{\$(?:this->)?(?:[a-zA-Z0-9_]+->)*([a-zA-Z0-9_]+)\}/', function ($m) {
                return '{'.$m[1].'}';
            }, $rawChannel);

            $cleanChannel = preg_replace_callback('/\$(?:this->)?(?:[a-zA-Z0-9_]+->)*([a-zA-Z0-9_]+)/', function ($m) {
                return '{'.$m[1].'}';
            }, $cleanChannel);

            return $cleanChannel;
        }

        return null;
    }

    private function scanClasses(): array
    {
        $validClasses = [];
        $paths = array_filter([app_path(), base_path('Modules'), base_path('modules')], 'is_dir');

        if (empty($paths)) {
            return [];
        }

        $this->log('🔍 Varrendo diretórios: '.implode(', ', $paths));

        $finder = new Finder;
        $finder->files()->in($paths)->name('*.php');

        foreach ($finder as $file) {
            $content = file_get_contents($file->getRealPath());

            if (! preg_match('/namespace\s+([a-zA-Z0-9_\\\\]+);/s', $content, $mNamespace)) {
                continue;
            }
            if (! preg_match('/class\s+([a-zA-Z0-9_]+)/s', $content, $mClass)) {
                continue;
            }

            $fullClass = $mNamespace[1].'\\'.$mClass[1];

            try {
                if (! class_exists($fullClass)) {
                    continue;
                }

                $reflection = new ReflectionClass($fullClass);

                if (! $reflection->implementsInterface(ShouldBroadcast::class)) {
                    continue;
                }

                $attrs = $reflection->getAttributes(AsyncApi::class);
                if (empty($attrs)) {
                    continue;
                }

                $this->log("💎 CLASSE VÁLIDA ENCONTRADA: $fullClass");
                $validClasses[] = $fullClass;

            } catch (Throwable $e) {
                continue;
            }
        }

        return array_unique($validClasses);
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
        Log::info("[AsyncApi] $message");
    }
}
