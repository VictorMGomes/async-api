<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi\Commands;

use Illuminate\Console\Command;
use Victormgomes\AsyncApi\Services\Docs\AsyncApiGenerator;

class AsyncApiCommand extends Command
{
    protected $signature = 'docs:asyncapi';

    protected $description = 'Generate AsyncAPI documentation automatically.';

    public function handle(AsyncApiGenerator $generator): int
    {
        $this->info('🚀 Starting event scan...');

        $docs = $generator->generate();

        $path = public_path('docs/asyncapi.json');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('✅ File generated successfully!');
        $this->info('📂 '.$path);

        return self::SUCCESS;
    }
}
