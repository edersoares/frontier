<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class FrontendProxyController
{
    public function __construct(protected Request $request)
    {
    }

    public function __invoke($uri, $config): Response
    {
        $accept = $this->request->header('accept', '*/*');
        $url = trim($config['url'], '/') . '/' . trim($uri, '/');

        $response = Http::withHeaders([
            'Accept' => $accept,
        ])->get($url);

        $content = $response->body();
        $contextType = $response->header('content-type');

        if ($config['replaces']) {
            $content = str_replace(
                array_keys($config['replaces']),
                array_values($config['replaces']),
                $content
            );
        }

        return new Response($content, headers: [
            'content-type' => $contextType,
        ]);
    }
}
