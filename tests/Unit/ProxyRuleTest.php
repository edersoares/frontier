<?php

declare(strict_types=1);

use Dex\Laravel\Frontier\ProxyRule;

test('a bare uri proxies everything under it with GET', function () {
    $rule = ProxyRule::fromString('/new');

    expect($rule->uri)->toBe('/new')
        ->and($rule->exact)->toBeFalse()
        ->and($rule->cache)->toBeFalse()
        ->and($rule->methods)->toBe(['GET'])
        ->and($rule->middleware)->toBe([])
        ->and($rule->replaces)->toBe([])
        ->and($rule->rewrite)->toBe([]);
});

test('exact segment proxies only the uri', function () {
    $rule = ProxyRule::fromString('/_vfs.json::exact');

    expect($rule->exact)->toBeTrue()
        ->and($rule->routeUri())->toBe('/_vfs.json')
        ->and($rule->url('https://cdn.test'))->toBe('https://cdn.test');
});

test('a rule without exact registers a catch-all route and appends the uri to the host', function () {
    $rule = ProxyRule::fromString('/new');

    expect($rule->routeUri())->toBe('/new/{uri?}')
        ->and($rule->url('https://cdn.test'))->toBe('https://cdn.test/new');
});

test('the root rule does not produce a double slash', function () {
    $rule = ProxyRule::fromString('/');

    expect($rule->routeUri())->toBe('/{uri?}')
        ->and($rule->url('https://cdn.test'))->toBe('https://cdn.test/');
});

test('cache segment enables the cache', function () {
    expect(ProxyRule::fromString('/new::cache')->cache)->toBeTrue();
});

test('methods segment uppercases and lists the accepted methods', function () {
    $rule = ProxyRule::fromString('/api::methods(get,post,,patch)');

    expect($rule->methods)->toBe(['GET', 'POST', 'PATCH']);
});

test('middleware segment is repeatable', function () {
    $rule = ProxyRule::fromString('/new::middleware(web)::middleware(auth)');

    expect($rule->middleware)->toBe(['web', 'auth']);
});

test('replace segment with an explicit replacement', function () {
    $rule = ProxyRule::fromString('/new::replace(/_nuxt/,https://cdn.test/_nuxt/)');

    expect($rule->replaces('https://other.test'))->toBe(['/_nuxt/' => 'https://cdn.test/_nuxt/']);
});

test('replace segment without a replacement prefixes the search with the host', function () {
    $rule = ProxyRule::fromString('/new::replace(/_nuxt/)');

    expect($rule->replaces)->toBe(['/_nuxt/' => null])
        ->and($rule->replaces('https://cdn.test'))->toBe(['/_nuxt/' => 'https://cdn.test/_nuxt/']);
});

test('rewrite segment without a replacement removes the search from the url', function () {
    $rule = ProxyRule::fromString('/favicon.ico::exact::rewrite(/favicon.ico)');

    expect($rule->rewrite)->toBe(['/favicon.ico' => '']);
});

test('rewrite segment with a replacement', function () {
    $rule = ProxyRule::fromString('/new::rewrite(/new,/latest)');

    expect($rule->rewrite)->toBe(['/new' => '/latest']);
});

test('segments are parsed regardless of their order', function () {
    $rule = ProxyRule::fromString('/api::cache::methods(post)::exact::middleware(auth)');

    expect($rule->cache)->toBeTrue()
        ->and($rule->exact)->toBeTrue()
        ->and($rule->methods)->toBe(['POST'])
        ->and($rule->middleware)->toBe(['auth']);
});

test('a segment that only looks like a function is ignored', function () {
    $rule = ProxyRule::fromString('/new::methods(get::cache(');

    expect($rule->methods)->toBe(['GET'])
        ->and($rule->cache)->toBeFalse();
});
