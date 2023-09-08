<?php

use Dex\Laravel\Frontier\Frontier;

test('`Frontier::http` simple use', function () {
    $config = Frontier::http('ui', '/web', 'http://localhost');

    expect($config->config())->toEqual([
        'ui' => [
            'type' => 'http',
            'endpoint' => '/web',
            'view' => 'http://localhost',
            'middleware' => [],
            'replaces' => [],
            'proxy' => [],
            'cache' => true,
            'headers' => [],
        ],
    ]);
});

test('`Frontier::http` using `proxy` method', function () {
    $config = Frontier::http('web', '/web', 'http://localhost')
        ->proxy('favicon.ico');

    expect($config->config())->toEqual([
        'web' => [
            'type' => 'http',
            'endpoint' => '/web',
            'view' => 'http://localhost',
            'middleware' => [],
            'replaces' => [],
            'proxy' => [
                'favicon.ico',
            ],
            'cache' => true,
            'headers' => [],
        ],
    ]);
});

test('`Frontier::http` using `replaceAsProxy` method', function () {
    $config = Frontier::http('web', '/web', 'http://localhost')
        ->replaceAsProxy('/favicon.ico');

    expect($config->config())->toEqual([
        'web' => [
            'type' => 'http',
            'endpoint' => '/web',
            'view' => 'http://localhost',
            'middleware' => [],
            'replaces' => [
                '/favicon.ico' => 'http://localhost/favicon.ico',
            ],
            'proxy' => [],
            'cache' => true,
            'headers' => [],
        ],
    ]);
});

test('`Frontier::http` using `cache` method', function () {
    $config = Frontier::http('web', '/web', 'http://localhost')
        ->cache();

    expect($config->config())->toEqual([
        'web' => [
            'type' => 'http',
            'endpoint' => '/web',
            'view' => 'http://localhost',
            'middleware' => [],
            'replaces' => [],
            'proxy' => [],
            'cache' => true,
            'headers' => [],
        ],
    ]);
});

test('`Frontier::http` using `cacheInProduction` method', function () {
    $config = Frontier::http('web', '/web', 'http://localhost')
        ->cacheInProduction();

    expect($config->config())->toEqual([
        'web' => [
            'type' => 'http',
            'endpoint' => '/web',
            'view' => 'http://localhost',
            'middleware' => [],
            'replaces' => [],
            'proxy' => [],
            'cache' => false,
            'headers' => [],
        ],
    ]);
});

test('`Frontier::http` using `noCache` method', function () {
    $config = Frontier::http('web', '/web', 'http://localhost')
        ->noCache();

    expect($config->config())->toEqual([
        'web' => [
            'type' => 'http',
            'endpoint' => '/web',
            'view' => 'http://localhost',
            'middleware' => [],
            'replaces' => [],
            'proxy' => [],
            'cache' => false,
            'headers' => [],
        ],
    ]);
});

test('`Frontier::http` using `headers` method', function () {
    $config = Frontier::http('web', '/web', 'http://localhost')
        ->header('Accept', 'text/html');

    expect($config->config())->toEqual([
        'web' => [
            'type' => 'http',
            'endpoint' => '/web',
            'view' => 'http://localhost',
            'middleware' => [],
            'replaces' => [],
            'proxy' => [],
            'cache' => true,
            'headers' => [
                'Accept' => 'text/html',
            ],
        ],
    ]);
});
