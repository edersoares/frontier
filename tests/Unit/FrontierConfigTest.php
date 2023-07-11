<?php

use Dex\Laravel\Frontier\Frontier;
use Dex\Laravel\Frontier\FrontierConfig;

beforeEach(fn () => Frontier::reset());

test('`Frontier` simple use', function () {
    $frontier = Frontier::instance();

    $frontier->push(new FrontierConfig('ui', 'view', '/web', 'index'));

    expect($frontier->config())->toEqual([
        'ui' => [
            'type' => 'view',
            'endpoint' => '/web',
            'view' => 'index',
            'middleware' => [],
            'replaces' => [],
        ],
    ]);
});

test('`Frontier` using all methods', function () {
    $frontier = Frontier::instance();

    $config = new FrontierConfig('ui', 'view', '/web', 'index');

    $config->middleware('web');
    $config->replace('/favicon.ico', '/favicon.svg');
    $frontier->push($config);

    expect($frontier->config())->toEqual([
        'ui' => [
            'type' => 'view',
            'endpoint' => '/web',
            'view' => 'index',
            'middleware' => [
                'web',
            ],
            'replaces' => [
                '/favicon.ico' => '/favicon.svg',
            ],
        ],
    ]);
});
