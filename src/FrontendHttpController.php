<?php

namespace Dex\Laravel\Frontier;

use Illuminate\Support\Facades\Http;

class FrontendHttpController
{
    public function __invoke($uri, $config)
    {
        $endpoint = trim($config['endpoint'], '/');
        $path = storage_path("frontier/$endpoint.html");

        if (file_exists($path)) {
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

        file_put_contents($path, $content);

        return $content;
    }
}
