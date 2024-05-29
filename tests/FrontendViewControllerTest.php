<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier\Tests;

use Dex\Laravel\Frontier\Frontier;

test('`view` controller', function () {
    Frontier::add([
        'type' => 'view',
        'endpoint' => 'view',
        'view' => 'frontier::index',
        'views' => [
            'frontier' => __DIR__ . '/../resources/html',
        ],
    ]);

    $this->get('/view')
        ->assertStatus(200)
        ->assertSeeText('Frontier by View');
});
