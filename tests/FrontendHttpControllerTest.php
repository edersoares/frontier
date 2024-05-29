<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier\Tests;

use Dex\Laravel\Frontier\Frontier;
use Illuminate\Support\Facades\Http;

test('`http` controller', function () {
    Frontier::add([
        'type' => 'http',
        'endpoint' => 'http',
        'view' => 'http://frontier.test',
        'cache' => false,
    ]);

    Frontier::add([
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
