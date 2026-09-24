<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FrontendProxyController
{
    public function __construct(protected Request $request)
    {
    }

    public function __invoke($uri, $config): Response
    {
        $method = $this->request->getMethod();
        $accept = $this->request->header('accept', '*/*');
        $url = trim($config['url'], '/') . '/' . trim($uri, '/');

        if ($config['rewrite']) {
            $url = str_replace(
                array_keys($config['rewrite']),
                array_values($config['rewrite']),
                $url
            );
        }

        $url = Frontier::resolveUrl($url, $this->request, $config);

        $cacheable = $method === 'GET' && $config['cache'];
        $cacheKey = 'frontier:proxy:' . sha1($url);
        $staleKey = 'frontier:proxy:stale:' . sha1($url);
        $store = Cache::store($config['cache_store']);

        if ($cacheable && ($cached = $store->get($cacheKey))) {
            return $this->response($cached['content'], Response::HTTP_OK, $cached['content_type'], 'hit');
        }

        $http = Http::withHeaders([
            'Accept' => $accept,
        ])
            ->timeout($config['timeout'])
            ->connectTimeout($config['connect_timeout']);

        try {
            $response = match ($method) {
                'GET' => $http->get($url),
                'HEAD' => $http->head($url),
                'POST' => $http->post($url, $this->request->all()),
                'PATCH' => $http->patch($url, $this->request->all()),
                'PUT' => $http->put($url, $this->request->all()),
                'DELETE' => $http->delete($url, $this->request->all()),
            };
        } catch (ConnectionException) {
            return $this->stale($store, $staleKey, $cacheable)
                ?? new Response('', Response::HTTP_GATEWAY_TIMEOUT);
        }

        if ($response->serverError() && ($stale = $this->stale($store, $staleKey, $cacheable))) {
            return $stale;
        }

        $content = $response->body();
        $contentType = $response->header('content-type');

        if ($config['replaces']) {
            $content = str_replace(
                array_keys($config['replaces']),
                array_values($config['replaces']),
                $content
            );
        }

        if ($cacheable && $response->successful()) {
            $cached = [
                'content' => $content,
                'content_type' => $contentType,
            ];

            $store->put($cacheKey, $cached, $config['cache_ttl']);
            $store->put($staleKey, $cached, $config['cache_stale_ttl']);
        }

        return $this->response($content, $response->status(), $contentType, $cacheable ? 'miss' : null);
    }

    private function stale(Repository $store, string $key, bool $cacheable): ?Response
    {
        if (!$cacheable || !($stale = $store->get($key))) {
            return null;
        }

        return $this->response($stale['content'], Response::HTTP_OK, $stale['content_type'], 'stale');
    }

    private function response(string $content, int $status, string $contentType, ?string $cache): Response
    {
        $headers = ['content-type' => $contentType];

        if ($cache) {
            $headers['x-frontier-cache'] = $cache;
        }

        return new Response($content, $status, $headers);
    }
}
