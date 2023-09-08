<?php

use Illuminate\Support\Facades\Http;

test('flow using `FrontendProxyController`', function () {
    $text = 'Frontier by Proxy';

    Http::fake([
        'frontier.test/proxy-uri' => Http::response($text),
    ]);

    $this->get('/proxy-uri')
        ->assertStatus(200)
        ->assertSeeText($text);
});
