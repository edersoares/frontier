<?php

use Dex\Laravel\Frontier\Frontier;

test('`Frontier::view` simple use', function () {
    $config = Frontier::view('ui', '/web', 'ui::index');

    expect($config->config())->toEqual([
        'ui' => [
            'type' => 'view',
            'endpoint' => '/web',
            'view' => 'ui::index',
            'views' => [],
            'publishes' => [],
            'middleware' => [],
            'replaces' => [],
        ],
    ]);
});

test('`Frontier::view` using `views` method', function () {
    $config = Frontier::view('ui', '/web', 'ui::index')
        ->views('ui', 'resources/views/frontier');

    expect($config->config())->toEqual([
        'ui' => [
            'type' => 'view',
            'endpoint' => '/web',
            'view' => 'ui::index',
            'views' => [
                'ui' => 'resources/views/frontier',
            ],
            'publishes' => [],
            'middleware' => [],
            'replaces' => [],
        ],
    ]);
});

test('`Frontier::view` using `publish` method', function () {
    $config = Frontier::view('ui', '/web', 'ui::index')
        ->publish('frontier', 'frontier/config/frontier.php', 'config/frontier.php');

    expect($config->config())->toEqual([
        'ui' => [
            'type' => 'view',
            'endpoint' => '/web',
            'view' => 'ui::index',
            'views' => [],
            'publishes' => [
                'frontier' => [
                    'frontier/config/frontier.php' => 'config/frontier.php',
                ],
            ],
            'middleware' => [],
            'replaces' => [],
        ],
    ]);
});
