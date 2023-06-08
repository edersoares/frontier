<?php

return [

    'frontier' => [

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
            explode(',', env('FRONTIER_FIND')),
            explode(',', env('FRONTIER_REPLACE_WITH')),
        ),

        'cache' => env('FRONTIER_CACHE', true),

    ],

];
