<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * @internal
 */
final class FrontendProxyController
{
    /**
     * @param array<string, mixed> $config
     */
    public function __invoke(Request $request, string $uri, array $config): Response
    {
        $method = $request->getMethod();
        $url = $this->url($request, $config['url'], $uri);

        if ($config['rewrite']) {
            $url = str_replace(
                array_keys($config['rewrite']),
                array_values($config['rewrite']),
                $url
            );
        }

        $url = Frontier::resolveUrl($url, $request, $config);

        $cacheable = $method === 'GET' && $config['cache'];
        $cacheKey = 'frontier:proxy:' . sha1($url);
        $staleKey = 'frontier:proxy:stale:' . sha1($url);
        $store = Cache::store($config['cache_store']);

        if ($cacheable && ($cached = $store->get($cacheKey))) {
            return $this->response($cached['content'], Response::HTTP_OK, $cached['headers'], 'hit');
        }

        $http = Http::withHeaders($this->requestHeaders($request, $config['request_headers']))
            ->timeout($config['timeout'])
            ->connectTimeout($config['connect_timeout']);

        [$http, $options] = $this->body($http, $request);

        try {
            $response = $http->send($method, $url, $options);
        } catch (ConnectionException) {
            return $this->stale($store, $staleKey, $cacheable)
                ?? new Response('', Response::HTTP_GATEWAY_TIMEOUT);
        }

        if ($response->serverError() && ($stale = $this->stale($store, $staleKey, $cacheable))) {
            return $stale;
        }

        $content = $response->body();
        $headers = $this->responseHeaders($response, $config['response_headers']);

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
                'headers' => $headers,
            ];

            $store->put($cacheKey, $cached, $config['cache_ttl']);
            $store->put($staleKey, $cached, $config['cache_stale_ttl']);
        }

        return $this->response($content, $response->status(), $headers, $cacheable ? 'miss' : null);
    }

    private function url(Request $request, string $base, string $uri): string
    {
        $url = trim($base, '/');

        if ($path = trim($uri, '/')) {
            $url .= '/' . $path;
        }

        if (str_ends_with($request->getPathInfo(), '/')) {
            $url .= '/';
        }

        if ($query = $request->getQueryString()) {
            $url .= '?' . $query;
        }

        return $url;
    }

    /**
     * Attach the body of the incoming request, as received, with its content type.
     *
     * Requests without a raw body but with parsed input, as built by the
     * Laravel test client, send the input as JSON.
     *
     * @return array{PendingRequest, array<string, mixed>}
     */
    private function body(PendingRequest $http, Request $request): array
    {
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return [$http, []];
        }

        $content = $request->getContent();

        if ($content !== '') {
            return [$http->withBody($content, $request->header('content-type', 'application/octet-stream')), []];
        }

        if ($request->all() !== []) {
            return [$http, ['json' => $request->all()]];
        }

        return [$http, []];
    }

    /**
     * The headers of the incoming request that are sent to the host.
     *
     * @param array<int, string> $names
     *
     * @return array<string, string>
     */
    private function requestHeaders(Request $request, array $names): array
    {
        $headers = [];

        foreach ($names as $name) {
            if ($request->hasHeader($name)) {
                $headers[$name] = $request->header($name);
            }
        }

        return $headers;
    }

    /**
     * The headers of the host response that are sent back to the client.
     *
     * @param array<int, string> $names
     *
     * @return array<string, string>
     */
    private function responseHeaders(ClientResponse $response, array $names): array
    {
        $headers = [];

        foreach ($names as $name) {
            if ($response->hasHeader($name)) {
                $headers[$name] = $response->header($name);
            }
        }

        return $headers;
    }

    private function stale(Repository $store, string $key, bool $cacheable): ?Response
    {
        if (!$cacheable || !($stale = $store->get($key))) {
            return null;
        }

        return $this->response($stale['content'], Response::HTTP_OK, $stale['headers'], 'stale');
    }

    /**
     * @param array<string, string> $headers
     */
    private function response(string $content, int $status, array $headers, ?string $cache): Response
    {
        if ($cache) {
            $headers['x-frontier-cache'] = $cache;
        }

        return new Response($content, $status, $headers);
    }
}
