<?php

declare(strict_types=1);

// Settings shared by every frontend of type `proxy`.
$proxy = [

    'host' => env('FRONTIER_PROXY_HOST', ''),

    'rules' => array_filter(explode('|', env('FRONTIER_PROXY_RULES', ''))),

    'timeout' => (int) env('FRONTIER_PROXY_TIMEOUT', 5),

    'connect_timeout' => (int) env('FRONTIER_PROXY_CONNECT_TIMEOUT', 2),

    'cache_store' => env('FRONTIER_PROXY_CACHE_STORE'),

    'cache_ttl' => (int) env('FRONTIER_PROXY_CACHE_TTL', 60),

    'cache_stale_ttl' => (int) env('FRONTIER_PROXY_CACHE_STALE_TTL', 86400),

    // Comma separated header names, see Frontier::REQUEST_HEADERS and Frontier::RESPONSE_HEADERS.
    'request_headers' => env('FRONTIER_PROXY_REQUEST_HEADERS', implode(',', Dex\Laravel\Frontier\Frontier::REQUEST_HEADERS)),

    'response_headers' => env('FRONTIER_PROXY_RESPONSE_HEADERS', implode(',', Dex\Laravel\Frontier\Frontier::RESPONSE_HEADERS)),

];

return [

    'frontier' => [

        'enabled' => env('FRONTIER_DEFAULT_ENABLED', true),

        'type' => env('FRONTIER_TYPE', 'view'),

        'endpoint' => env('FRONTIER_ENDPOINT', 'frontier'),

        'view' => env('FRONTIER_VIEW', 'frontier::index'),

        // https://laravel.com/docs/packages#views
        'views' => [
            'frontier' => env('FRONTIER_VIEWS_PATH') ? base_path(env('FRONTIER_VIEWS_PATH')) : __DIR__ . '/../resources/html',
        ],

        // https://laravel.com/docs/packages#publishing-views
        // https://laravel.com/docs/packages#publishing-file-groups
        'publishes' => [
            'frontier' => [
                __DIR__ . '/../config/frontier.php' => config_path('frontier.php'),
            ],
        ],

        // https://laravel.com/docs/middleware
        'middleware' => [],

        'replaces' => array_combine(
            array_filter(explode(',', env('FRONTIER_FIND', ''))),
            array_filter(explode(',', env('FRONTIER_REPLACE_WITH', ''))),
        ),

        'cache' => env('FRONTIER_CACHE', true),

        'headers' => [
            'Accept' => 'text/html',
        ],

        ...$proxy,

    ],

    'proxy' => [

        'enabled' => env('FRONTIER_PROXY_ENABLED', true),

        'type' => 'proxy',

        ...$proxy,

        // Deprecated: the fallback to FRONTIER_VIEW will be removed in 1.0. Use FRONTIER_PROXY_HOST.
        'host' => env('FRONTIER_PROXY_HOST', env('FRONTIER_VIEW', '')),

    ],

];
