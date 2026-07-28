<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Victormgomes\AsyncApi\Services\Docs\AsyncApiGenerator;

Route::middleware(config('async-api.middleware', []))->group(function () {
    Route::get('/docs/broadcast', function () {
        return view('async-api::asyncapi');
    })->name('docs.ws.ui');

    Route::get('/docs/broadcast/json', function (AsyncApiGenerator $generator) {
        ob_start();
        $schema = $generator->generate();
        ob_end_clean();

        return response()->json($schema);
    })->name('docs.ws.json');
});
