<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi\Services\Docs;

use Illuminate\Support\Facades\Log;
use Laravel\Ranger\Components\BroadcastEvent;
use Laravel\Ranger\Ranger;
use Laravel\Surveyor\Analyzer\Analyzer;
use Laravel\Surveyor\Types\ArrayType;
use Laravel\Surveyor\Types\ClassType;
use Laravel\Surveyor\Types\StringType;
use ReflectionClass;
use stdClass;
use Throwable;
use Victormgomes\AsyncApi\Attributes\AsyncApi;
use Victormgomes\AsyncApi\Attributes\AsyncApiIgnore;

class AsyncApiGenerator
{
    public function __construct(
        protected SchemaConverter $schemaConverter
    ) {}

    public function generate(): array
    {
        $this->log('🚀 INICIANDO GERAÇÃO (GLOBAL CONFIG MODE)...');

        $info = [
            'title' => config('async-api.info_title', config('app.name').' Broadcasting API'),
            'version' => config('async-api.info_version', '1.0.0'),
            'description' => config('async-api.info_description', 'AsyncAPI documentation for the broadcasting API'),
        ];

        $protocol = config('async-api.server_scheme', 'https') === 'https' ? 'wss' : 'ws';
        $host = config('async-api.server_host', 'localhost').':'.config('async-api.server_port', 8080);

        $servers = [
            'default' => [
                'host' => $host,
                'protocol' => $protocol,
                'protocolVersion' => '1.3',
                'description' => config('async-api.server_description', 'Laravel Reverb Server (Pusher Protocol)'),
                'security' => [
                    ['$ref' => '#/components/securitySchemes/bearerAuth'],
                ],
                'bindings' => [
                    'ws' => [
                        'method' => 'GET',
                        'query' => [
                            'type' => 'object',
                            'properties' => [
                                'appKey' => [
                                    'type' => 'string',
                                    'description' => 'The Reverb/Pusher App Key',
                                    'example' => config('async-api.server_app_key', 'your-app-key-here'),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $structure = [
            'asyncapi' => config('async-api.asyncapi_version', '3.0.0'),
            'info' => array_filter($info),
            'defaultContentType' => config('async-api.default_content_type', 'application/json'),
            'servers' => $servers,
            'channels' => [],
            'operations' => [],
            'components' => [
                'schemas' => [],
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => config('async-api.security_description', 'Enter your Sanctum token to authenticate with the broadcasting server.'),
                    ],
                ],
            ],
        ];

        $paths = array_filter([app_path(), base_path('Modules'), base_path('modules')], 'is_dir');

        $this->log('🔍 Varrendo a aplicação com Ranger nos diretórios: '.implode(', ', $paths));

        $ranger = app(Ranger::class);
        $ranger->setAppPaths(...$paths);

        $analyzer = app(Analyzer::class);

        $validClassesCount = 0;

        $ranger->onBroadcastEvent(function (BroadcastEvent $event) use (&$structure, $analyzer, &$validClassesCount) {
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

    private function processEvent(BroadcastEvent $event, Analyzer $analyzer, array &$structure): bool
    {
        $className = $event->className;

        try {
            $reflection = new ReflectionClass($className);

            // Permite ignorar o evento se tiver o atributo AsyncApiIgnore
            if (! empty($reflection->getAttributes(AsyncApiIgnore::class))) {
                $this->log("   🚫 Ignorado por AsyncApiIgnore: $className");

                return false;
            }

            $attributes = $reflection->getAttributes(AsyncApi::class);
            $attr = ! empty($attributes) ? $attributes[0]->newInstance() : new AsyncApi;

            $this->log("📝 Processando Classe: $className");

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

            // Usa o nome da mensagem extraído nativamente pelo Surveyor/Ranger via $event->name
            $eventName = $attr->name ?? $event->name;

            // Delega a geração do schema inteiramente ao Surveyor Type
            $payloadSchema = $this->schemaConverter->convertSurveyorType($event->data);

            // Envolve o schema caso o Surveyor retorne apenas propriedades isoladas de objeto e não o object root
            if (isset($payloadSchema['properties']) && ! isset($payloadSchema['type'])) {
                $payloadSchema['type'] = 'object';
            }

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

            $this->log("   ✨ Sucesso! Adicionado ao canal: $channelUri com schema dinâmico.");

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
            $this->log('   💀 ERRO AO INFERIR CANAL via Surveyor: '.$e->getMessage());

            return null;
        }
    }

    private function extractChannelUriFromType(mixed $type): ?string
    {
        if ($type instanceof ClassType) {
            $prop = new \ReflectionProperty(ClassType::class, 'constructorArguments');
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

    private function log(string $message): void
    {
        if (config('async-api.debug', true)) {
            Log::info("[AsyncApi] $message");
        }
    }
}
