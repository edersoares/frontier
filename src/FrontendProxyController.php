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

        if ($config['rewrite']) {
            $url = str_replace(
                array_keys($config['rewrite']),
                array_values($config['rewrite']),
                $url
            );
        }

        $http = Http::withHeaders([
            'Accept' => $accept,
        ]);

        $response = match ($this->request->getMethod()) {
            'GET' => $http->get($url),
            'HEAD' => $http->head($url),
            'POST' => $http->post($url, $this->request->all()),
            'PATCH' => $http->patch($url, $this->request->all()),
            'PUT' => $http->put($url, $this->request->all()),
            'DELETE' => $http->delete($url, $this->request->all()),
        };

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
