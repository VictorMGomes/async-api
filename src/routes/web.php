<?php

declare(strict_types=1);

use Victormgomes\AsyncApi\Services\Docs\AsyncApiGenerator;

Route::get('/docs/ws', function () {
    return view('async-api::asyncapi');
})->name('docs.ws.ui');

Route::get('/docs/ws/json', function (AsyncApiGenerator $generator) {
    ob_start();
    $schema = $generator->generate();
    ob_end_clean();

    return response()->json($schema);
})->name('docs.ws.json');
