<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use Illuminate\Support\Facades\Http;

/**
 * @internal
 *
 * @deprecated The `http` type will be removed in 1.0. Use the `proxy` type instead.
 */
final class FrontendHttpController
{
    /**
     * @param array<string, mixed> $config
     */
    public function __invoke(string $uri, array $config): string
    {
        $endpoint = trim($config['endpoint'], '/');
        $path = storage_path("framework/views/frontier-$endpoint.html");

        if ($config['cache'] && file_exists($path)) {
            return file_get_contents($path);
        }

        $content = Http::withHeaders($config['headers'] ?? [])
            ->get($config['view'])
            ->body();

        $content = str_replace(
            array_keys($config['replaces'] ?? []),
            array_values($config['replaces'] ?? []),
            $content
        );

        if ($config['cache']) {
            file_put_contents($path, $content);
        }

        return $content;
    }
}
