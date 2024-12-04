<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

class Frontier
{
    public static function add(array $config): void
    {
        if (empty($config['enabled'])) {
            return;
        }

        match ($config['type']) {
            'http' => self::http($config),
            'proxy' => self::proxy($config),
            'view' => self::view($config),
        };
    }

    public static function addFromConfig(string $key): void
    {
        self::add(config($key, []));
    }

    private static function http(array $config): void
    {
        self::frontend($config);
    }

    private static function proxy(array $config): void
    {
        $host = $config['host'] ?? '';
        $rules = $config['rules'] ?? [];

        foreach ($rules as $rule) {
            $segments = explode('::', $rule);

            $url = $host;
            $uri = $segments[0];
            $methods = [];
            $replaces = [];
            $rewrite = [];
            $middleware = [];
            $cache = false;
            $proxyAll = true;

            foreach ($segments as $segment) {
                $cache = false;

                if ($segment === 'cache') {
                    $cache = true;
                }

                if ($segment === 'exact') {
                    $proxyAll = false;
                }

                $methods = ['GET']; // Need to be reseted each loop

                if (str_starts_with($segment, 'methods(') && str_ends_with($segment, ')')) {
                    $replace = substr($segment, 8, -1);

                    $methods = explode(',', $replace . ',');
                    $methods = array_filter($methods);
                    $methods = array_map(fn ($method) => strtoupper($method), $methods);
                }

                if (str_starts_with($segment, 'middleware(') && str_ends_with($segment, ')')) {
                    $replace = substr($segment, 11, -1);

                    $middleware[] = $replace;
                }

                if (str_starts_with($segment, 'replace(') && str_ends_with($segment, ')')) {
                    $replace = substr($segment, 8, -1);

                    [$search, $replace] = explode(',', $replace . ',');

                    if (empty($replace)) {
                        $replace = $url . $search;
                    }

                    $replaces[$search] = $replace;
                }

                if (str_starts_with($segment, 'rewrite(') && str_ends_with($segment, ')')) {
                    $replace = substr($segment, 8, -1);

                    [$search, $replace] = explode(',', $replace . ',');

                    $rewrite[$search] = $replace;
                }
            }

            if ($proxyAll) {
                $url = $url . $uri;
                $uri .= '/{uri?}';
            }

            $uri = str_replace('//', '/', $uri);

            Route::match($methods, $uri, FrontendProxyController::class)
                ->middleware($middleware)
                ->where('uri', '.*')
                ->setDefaults([
                    'uri' => $uri,
                    'config' => [
                        'url' => $url,
                        'replaces' => $replaces,
                        'rewrite' => $rewrite,
                        'methods' => $methods,
                        'cache' => $cache,
                    ],
                ]);
        }
    }

    private static function view(array $config): void
    {
        self::frontend($config);
    }

    private static function frontend($config): void
    {
        $controller = match ($config['type']) {
            'http' => FrontendHttpController::class,
            'view' => FrontendViewController::class,
            default => throw new InvalidArgumentException('Unknown controller type'),
        };

        Route::get($config['endpoint'] . '/{uri?}', $controller)
            ->middleware($config['middleware'] ?? [])
            ->where('uri', '.*')
            ->setDefaults([
                'uri' => '',
                'config' => $config,
            ]);
    }
}
