<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

/**
 * A single proxy rule parsed from FRONTIER_PROXY_RULES.
 *
 * A rule starts with the URI to proxy followed by optional segments
 * separated by "::", for example "/new::cache::methods(get,post)".
 */
final class ProxyRule
{
    /**
     * @param array<int, string> $methods
     * @param array<int, string> $middleware
     * @param array<string, string|null> $replaces null means the search prefixed with the host
     * @param array<string, string> $rewrite
     */
    public function __construct(
        public readonly string $uri,
        public readonly bool $exact = false,
        public readonly bool $cache = false,
        public readonly array $methods = ['GET'],
        public readonly array $middleware = [],
        public readonly array $replaces = [],
        public readonly array $rewrite = [],
    ) {
    }

    public static function fromString(string $rule): self
    {
        $segments = explode('::', $rule);
        $uri = array_shift($segments);

        $exact = false;
        $cache = false;
        $methods = ['GET'];
        $middleware = [];
        $replaces = [];
        $rewrite = [];

        foreach ($segments as $segment) {
            if ($segment === 'exact') {
                $exact = true;
            }

            if ($segment === 'cache') {
                $cache = true;
            }

            if (($arguments = self::arguments($segment, 'methods')) !== null) {
                $methods = array_filter(explode(',', $arguments));
                $methods = array_map('strtoupper', $methods);
                $methods = array_values($methods);
            }

            if (($arguments = self::arguments($segment, 'middleware')) !== null) {
                $middleware[] = $arguments;
            }

            if (($arguments = self::arguments($segment, 'replace')) !== null) {
                [$search, $replace] = self::pair($arguments);

                $replaces[$search] = $replace === '' ? null : $replace;
            }

            if (($arguments = self::arguments($segment, 'rewrite')) !== null) {
                [$search, $replace] = self::pair($arguments);

                $rewrite[$search] = $replace;
            }
        }

        return new self($uri, $exact, $cache, $methods, $middleware, $replaces, $rewrite);
    }

    /**
     * The base URL requested from the host for this rule.
     */
    public function url(string $host): string
    {
        return $this->exact ? $host : $host . $this->uri;
    }

    /**
     * The URI registered in the router.
     */
    public function routeUri(): string
    {
        $uri = $this->exact ? $this->uri : $this->uri . '/{uri?}';

        return str_replace('//', '/', $uri);
    }

    /**
     * The replacements applied to the response body, resolved against the host.
     *
     * @return array<string, string>
     */
    public function replaces(string $host): array
    {
        $replaces = [];

        foreach ($this->replaces as $search => $replace) {
            $replaces[$search] = $replace ?? $host . $search;
        }

        return $replaces;
    }

    /**
     * The content between the parentheses of "name(...)", or null when the
     * segment is not that function.
     */
    private static function arguments(string $segment, string $name): ?string
    {
        if (!str_starts_with($segment, $name . '(') || !str_ends_with($segment, ')')) {
            return null;
        }

        return substr($segment, strlen($name) + 1, -1);
    }

    /**
     * @return array{string, string}
     */
    private static function pair(string $arguments): array
    {
        [$first, $second] = explode(',', $arguments . ',');

        return [$first, $second];
    }
}
