<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

class Frontier
{
    protected static ?Closure $urlResolver = null;

    /**
     * Resolve the final URL requested from the proxied host at request time.
     *
     * The callback receives the URL, the current request and the rule config,
     * and must return the URL to use. Pass null to remove the resolver.
     */
    public static function resolveUrlUsing(?Closure $resolver): void
    {
        static::$urlResolver = $resolver;
    }

    public static function resolveUrl(string $url, Request $request, array $config): string
    {
        if (static::$urlResolver === null) {
            return $url;
        }

        return (static::$urlResolver)($url, $request, $config);
    }

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
            $methods = ['GET'];

            foreach ($segments as $segment) {
                if ($segment === 'cache') {
                    $cache = true;
                }

                if ($segment === 'exact') {
                    $proxyAll = false;
                }

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

            $proxyUri = $uri;

            if ($proxyAll) {
                $url .= $proxyUri;
                $routeUri = $proxyUri . '/{uri?}';
            } else {
                $routeUri = $proxyUri;
            }

            $routeUri = str_replace('//', '/', $routeUri);

            Route::match($methods, $routeUri, FrontendProxyController::class)
                ->middleware($middleware)
                ->where('uri', '.*')
                ->setDefaults([
                    'uri' => $proxyUri,
                    'config' => [
                        'url' => $url,
                        'replaces' => $replaces,
                        'rewrite' => $rewrite,
                        'methods' => $methods,
                        'cache' => $cache,
                        'timeout' => (int) ($config['timeout'] ?? 5),
                        'connect_timeout' => (int) ($config['connect_timeout'] ?? 2),
                        'cache_store' => $config['cache_store'] ?? null,
                        'cache_ttl' => (int) ($config['cache_ttl'] ?? 60),
                        'cache_stale_ttl' => (int) ($config['cache_stale_ttl'] ?? 86400),
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
            default => throw new InvalidArgumentException('Unknown controller type'), // @codeCoverageIgnore
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
