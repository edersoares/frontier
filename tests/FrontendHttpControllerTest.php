<?php

namespace Dex\Laravel\Frontier\Tests;

use Illuminate\Support\Facades\Http;

class FrontendHttpControllerTest extends TestCase
{
    public function testHttpController(): void
    {
        $http = storage_path('framework/views/frontier-http.html');
        $httpWithCache = storage_path('framework/views/frontier-http-with-cache.html');
        $text = 'Frontier by HTTP';

        Http::fake([
            'localhost' => Http::response($text),
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
    }
}
