<?php

declare(strict_types=1);

use Dex\Laravel\Frontier\Frontier;
use Illuminate\Support\Facades\Http;

test('assert not found when Frontier is disabled', function () {
    Frontier::add([
        'enabled' => false,
        'type' => 'proxy',
        'host' => 'frontier.test',
        'rules' => [
            '/disabled::exact',
        ],
    ]);

    Http::fake([
        'frontier.test/disabled' => Http::response('Frontier is disabled'),
    ]);

    $this->get('disabled')
        ->assertNotFound();
});
