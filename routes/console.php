<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('openapi:generate', function () {
    $this->info('Generating OpenAPI documentation...');

    $result = $this->call('l5-swagger:generate');

    if ($result === 0) {
        $this->info('OpenAPI documentation generated successfully.');
    }

    return $result;
})->purpose('Generate OpenAPI documentation from current routes and annotations');
