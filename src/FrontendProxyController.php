<?php

namespace Dex\Laravel\Frontier;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class FrontendProxyController
{
    public function __invoke($uri, $config): Response
    {
        $url = trim($config['view'], '/') . '/' . trim($uri, '/');

        $response = Http::get($url);

        $content = $response->body();
        $contextType = $response->header('content-type');

        return new Response($content, headers: [
            'content-type' => $contextType,
        ]);
    }
}
