<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

class Frontier
{
    public const TYPES = ['http', 'proxy', 'view'];

    /**
     * Headers of the incoming request sent to the proxied host by default.
     */
    public const REQUEST_HEADERS = ['accept', 'accept-language', 'user-agent'];

    /**
     * Headers of the host response sent back to the client by default.
     */
    public const RESPONSE_HEADERS = ['content-type', 'cache-control', 'etag', 'last-modified', 'content-disposition'];

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

    /**
     * @internal
     *
     * @param array<string, mixed> $config
     */
    public static function resolveUrl(string $url, Request $request, array $config): string
    {
        if (static::$urlResolver === null) {
            return $url;
        }

        return (static::$urlResolver)($url, $request, $config);
    }

    /**
     * Forget everything registered at runtime, such as the URL resolver.
     */
    public static function flush(): void
    {
        static::$urlResolver = null;
    }

    /**
     * Register the routes of a frontend.
     *
     * @param array<string, mixed> $config
     *
     * @throws InvalidArgumentException when the config is invalid
     */
    public static function add(array $config): void
    {
        if (empty($config['enabled'])) {
            return;
        }

        $type = $config['type'] ?? null;

        if (!is_string($type) || !in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown Frontier type [%s]. Expected one of: %s.',
                is_scalar($type) ? $type : gettype($type),
                implode(', ', self::TYPES)
            ));
        }

        match ($type) {
            'http' => self::http($config),
            'proxy' => self::proxy($config),
            'view' => self::view($config),
        };
    }

    public static function addFromConfig(string $key): void
    {
        self::add(config($key, []));
    }

    /**
     * @deprecated The `http` type will be removed in 1.0. Use the `proxy` type instead.
     *
     * @param array<string, mixed> $config
     */
    private static function http(array $config): void
    {
        trigger_error(
            'The `http` Frontier type is deprecated and will be removed in 1.0. Use the `proxy` type instead.',
            E_USER_DEPRECATED
        );

        self::frontend(FrontendHttpController::class, $config);
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function proxy(array $config): void
    {
        $host = $config['host'] ?? '';
        $rules = $config['rules'] ?? [];

        if (!is_array($rules)) {
            throw new InvalidArgumentException('The proxy `rules` must be an array of rule strings.');
        }

        if ($rules !== [] && (!is_string($host) || $host === '')) {
            throw new InvalidArgumentException('The proxy `host` is required when there are rules.');
        }

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
                        'cache_max_size' => (int) ($config['cache_max_size'] ?? 1048576),
                        'request_headers' => self::headers($config['request_headers'] ?? self::REQUEST_HEADERS),
                        'response_headers' => self::headers($config['response_headers'] ?? self::RESPONSE_HEADERS),
                    ],
                ]);
        }
    }

    /**
     * Normalize a list of header names, given as an array or a comma separated string.
     *
     * @return array<int, string>
     */
    private static function headers(mixed $names): array
    {
        if (is_string($names)) {
            $names = explode(',', $names);
        }

        if (!is_array($names)) {
            throw new InvalidArgumentException('The proxy header lists must be arrays or comma separated strings.');
        }

        $names = array_map(fn (string $name) => strtolower(trim($name)), $names);

        return array_values(array_filter($names));
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function view(array $config): void
    {
        self::frontend(FrontendViewController::class, $config);
    }

    /**
     * @param class-string $controller
     * @param array<string, mixed> $config
     */
    private static function frontend(string $controller, array $config): void
    {
        foreach (['endpoint', 'view'] as $key) {
            if (!is_string($config[$key] ?? null) || $config[$key] === '') {
                throw new InvalidArgumentException(sprintf(
                    'The `%s` of a `%s` frontend is required.',
                    $key,
                    $config['type']
                ));
            }
        }

        Route::get($config['endpoint'] . '/{uri?}', $controller)
            ->middleware($config['middleware'] ?? [])
            ->where('uri', '.*')
            ->setDefaults([
                'uri' => '',
                'config' => $config,
            ]);
    }
}
