<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier\Tests;

use Dex\Laravel\Frontier\Frontier;
use Illuminate\Support\Facades\Http;

test('`http` controller', function () {
    Frontier::add([
        'enabled' => true,
        'type' => 'http',
        'endpoint' => 'http',
        'view' => 'http://frontier.test',
        'cache' => false,
    ]);

    Frontier::add([
        'enabled' => true,
        'type' => 'http',
        'endpoint' => 'http-with-cache',
        'view' => 'http://frontier.test',
        'cache' => true,
    ]);

    $http = storage_path('framework/views/frontier-http.html');
    $httpWithCache = storage_path('framework/views/frontier-http-with-cache.html');
    $text = 'Frontier by HTTP';

    Http::fake([
        'frontier.test' => Http::response($text),
    ]);

    $this->get('/http')
        ->assertStatus(200)
        ->assertSeeText($text);

    $this->assertFileDoesNotExist($http);

    $this->get('/http-with-cache')
        ->assertStatus(200)
        ->assertSeeText($text);

    $this->assertFileExists($httpWithCache);
    $this->assertStringEqualsFile($httpWithCache, $text);

    $this->get('/http-with-cache')
        ->assertStatus(200)
        ->assertSeeText($text);

    // Remove cache
    $this->artisan('view:clear');
});

test('`http` controller triggers a deprecation when registered', function () {
    $deprecation = null;

    set_error_handler(function (int $level, string $message) use (&$deprecation) {
        $deprecation = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        Frontier::add([
            'enabled' => true,
            'type' => 'http',
            'endpoint' => 'deprecated',
            'view' => 'http://frontier.test',
            'cache' => false,
        ]);
    } finally {
        restore_error_handler();
    }

    expect($deprecation)->toContain('`http` Frontier type is deprecated');
});
