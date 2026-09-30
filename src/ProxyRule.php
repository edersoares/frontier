<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use InvalidArgumentException;

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

    /**
     * @throws InvalidArgumentException when the rule has no URI or an unknown segment
     */
    public static function fromString(string $rule): self
    {
        $segments = explode('::', $rule);
        $uri = array_shift($segments);

        if ($uri === '') {
            throw new InvalidArgumentException(sprintf('The proxy rule [%s] must start with the URI to proxy.', $rule));
        }

        $exact = false;
        $cache = false;
        $methods = ['GET'];
        $middleware = [];
        $replaces = [];
        $rewrite = [];

        foreach ($segments as $segment) {
            [$name, $arguments] = self::segment($segment);

            match ($name) {
                'exact' => $exact = true,
                'cache' => $cache = true,
                'methods' => $methods = array_values(array_map('strtoupper', array_filter(explode(',', (string) $arguments)))),
                'middleware' => $middleware[] = (string) $arguments,
                'replace' => $replaces[self::pair($arguments)[0]] = self::pair($arguments)[1] === '' ? null : self::pair($arguments)[1],
                'rewrite' => $rewrite[self::pair($arguments)[0]] = self::pair($arguments)[1],
                default => throw new InvalidArgumentException(sprintf(
                    'Unknown segment [%s] in the proxy rule [%s]. Expected one of: exact, cache, methods(...), middleware(...), replace(...), rewrite(...).',
                    $segment,
                    $rule
                )),
            };
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
     * Split "name" or "name(arguments)" into its name and arguments. The
     * arguments are null when the segment has no parentheses.
     *
     * @return array{string, string|null}
     */
    private static function segment(string $segment): array
    {
        if (str_ends_with($segment, ')') && ($open = strpos($segment, '(')) !== false) {
            return [substr($segment, 0, $open), substr($segment, $open + 1, -1)];
        }

        return [$segment, null];
    }

    /**
     * @return array{string, string}
     */
    private static function pair(?string $arguments): array
    {
        [$first, $second] = explode(',', (string) $arguments . ',');

        return [$first, $second];
    }
}
