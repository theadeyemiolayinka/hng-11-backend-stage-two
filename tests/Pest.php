<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

uses(Tests\TestCase::class)->in('Feature', 'Unit');

// once(function () {
//     Artisan::call('migrate:fresh');
//     Artisan::call('php artisan passport:optimized-install');
// });

beforeEach(function () {
    Log::info('Running Before Each');
    Artisan::call('migrate:fresh');
    Artisan::call('passport:optimized-install --force');
    Artisan::call('migrate');
});

afterEach(function () {
    Log::info('Running After Each');
    Artisan::call('migrate:rollback');
});
