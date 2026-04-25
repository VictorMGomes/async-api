<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi\Commands;

use Illuminate\Console\Command;
use Victormgomes\AsyncApi\Services\Docs\AsyncApiGenerator;
use Victormgomes\AsyncApi\Services\Docs\SchemaConverter;

class GenerateAsyncApiDocs extends Command
{
    protected $signature = 'docs:asyncapi';

    protected $description = 'Gera a documentação AsyncAPI automaticamente.';

    public function handle()
    {
        $this->info('🚀 Iniciando escaneamento de eventos...');

        // Injeção de dependência manual (ou via container)
        $converter = new SchemaConverter;
        $generator = new AsyncApiGenerator($converter);

        $docs = $generator->generate();

        $path = public_path('docs/asyncapi.json');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('✅ Arquivo gerado com sucesso!');
        $this->info('📂 '.$path);
    }
}
