<?php

namespace Dex\Laravel\Frontier;

use Illuminate\Support\Facades\Http;

class FrontendHttpController
{
    public function __invoke($uri, $config)
    {
        $content = Http::withHeaders($config['headers'])
            ->get($config['url'])
            ->body();

        return str_replace(
            array_keys($config['replaces'] ?? []),
            array_values($config['replaces'] ?? []),
            $content
        );
    }
}
