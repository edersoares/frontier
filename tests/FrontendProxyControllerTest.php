<?php

namespace Dex\Laravel\Frontier\Tests;

use Illuminate\Support\Facades\Http;

class FrontendProxyControllerTest extends TestCase
{
    public function testProxyController(): void
    {
        $text = 'Frontier by Proxy';

        Http::fake([
            'localhost/proxy-uri' => Http::response($text),
        ]);

        $this->get('/proxy-uri')
            ->assertStatus(200)
            ->assertSeeText($text);
    }
}
