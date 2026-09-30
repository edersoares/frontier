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
            $rule = ProxyRule::fromString($rule);

            Route::match($rule->methods, $rule->routeUri(), FrontendProxyController::class)
                ->middleware($rule->middleware)
                ->where('uri', '.*')
                ->setDefaults([
                    'uri' => $rule->exact ? $rule->uri : '',
                    'config' => [
                        'url' => $rule->url($host),
                        'replaces' => $rule->replaces($host),
                        'rewrite' => $rule->rewrite,
                        'methods' => $rule->methods,
                        'cache' => $rule->cache,
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
