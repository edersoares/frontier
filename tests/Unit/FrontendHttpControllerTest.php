<?php

use Illuminate\Support\Facades\Http;

test('flow using `FrontendHttpController`', function () {
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
