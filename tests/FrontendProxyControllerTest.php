<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier\Tests;

use Dex\Laravel\Frontier\Frontier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config(['cache.default' => 'array']);

    Frontier::add([
    'enabled' => true,
    'type' => 'proxy',
    'host' => 'frontier.test',
    'rules' => [
        '/favicon.ico::exact',
        '/exact/replace::exact::replace(/exact/replace,https://frontier.test/another/exact/replace)',
        '/replace::replace(/replace)',
        '/rewrite::rewrite(/rewrite,/url-rewrite)',
        '/all::replace(Replace,is amazing!)',
        '/web',
        '/all-methods::methods(get,head,options,post,put,patch,delete)',
        '/with-cache::cache',
        '/middleware::middleware(auth)',
        '/cache-first::cache::middleware(Illuminate\\Routing\\Middleware\\SubstituteBindings)',
        '/methods-first::methods(get,post)::replace(Replace,Done)',
    ],
]);
});

afterEach(fn () => Frontier::resolveUrlUsing(null));

function cacheKey(): string
{
    $url = Http::recorded()->first()[0]->url();

    return 'frontier:proxy:' . sha1($url);
}

test('proxy exact route', function () {
    Http::fake([
        'frontier.test/favicon.ico' => Http::response('Frontier Favicon'),
    ]);

    $this->get('favicon.ico')
        ->assertContent('Frontier Favicon')
        ->assertOk();

    $this->get('favicon.icon')
        ->assertNotFound();

    $this->get('favicon.ico/ico')
        ->assertNotFound();
});

test('proxy all routes', function () {
    Http::fake([
        'frontier.test/web/*' => Http::response('Frontier Favicon'),
    ]);

    $this->get('/web')
        ->assertOk();

    $this->get('/web/one')
        ->assertOk();

    $this->get('web/two')
        ->assertOk();
});

test('proxy exact and replace', function () {
    Http::fake([
        'frontier.test/exact/replace' => Http::response('Running: /exact/replace'),
    ]);

    $this->get('/exact/replace')
        ->assertContent('Running: https://frontier.test/another/exact/replace')
        ->assertOk();

    $this->get('exact/replace/more')
        ->assertNotFound();
});

test('proxy all routes and replace', function () {
    Http::fake([
        'frontier.test/all/*' => Http::response('Frontier Replace'),
    ]);

    $this->get('/all')
        ->assertContent('Frontier is amazing!')
        ->assertOk();

    $this->get('/all/one')
        ->assertContent('Frontier is amazing!')
        ->assertOk();

    $this->get('all/two')
        ->assertContent('Frontier is amazing!')
        ->assertOk();
});

test('proxy and replace using base URL', function () {
    Http::fake([
        'frontier.test/replace/*' => Http::response('Frontier is running in: /replace'),
    ]);

    $this->get('/replace')
        ->assertContent('Frontier is running in: frontier.test/replace')
        ->assertOk();
});

test('proxy all routes and rewrite', function () {
    Http::fake([
        'frontier.test/url-rewrite/*' => Http::response('Frontier Rewrite URL'),
    ]);

    $this->get('/rewrite')
        ->assertContent('Frontier Rewrite URL')
        ->assertOk();

    Http::assertSent(fn (Request $request) => $request->method() === 'GET');
});

test('proxy POST request', function () {
    Http::fake([
        'frontier.test/all-methods/*' => Http::response('OK'),
    ]);

    $this->post('/all-methods')
        ->assertContent('OK')
        ->assertOk();

    Http::assertSent(fn (Request $request) => $request->method() === 'POST');
});

test('proxy HEAD request', function () {
    Http::fake([
        'frontier.test/all-methods/*' => Http::response(),
    ]);

    $this->head('/all-methods')
        ->assertOk();

    Http::assertSent(fn (Request $request) => $request->method() === 'HEAD');
});

test('proxy PATCH request', function () {
    Http::fake([
        'frontier.test/all-methods/*' => Http::response(),
    ]);

    $this->patch('/all-methods')
        ->assertOk();

    Http::assertSent(fn (Request $request) => $request->method() === 'PATCH');
});

test('proxy PUT request', function () {
    Http::fake([
        'frontier.test/all-methods/*' => Http::response(),
    ]);

    $this->put('/all-methods')
        ->assertOk();

    Http::assertSent(fn (Request $request) => $request->method() === 'PUT');
});

test('proxy DELETE request', function () {
    Http::fake([
        'frontier.test/all-methods/*' => Http::response('OK'),
    ]);

    $this->delete('/all-methods')
        ->assertContent('OK')
        ->assertOk();

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE');
});

test('proxy and do cache', function () {
    $text = 'Frontier by HTTP';

    Http::fake([
        'frontier.test/*' => Http::response($text, headers: ['Content-Type' => 'application/javascript']),
    ]);

    $this->get('/with-cache')
        ->assertOk()
        ->assertHeader('x-frontier-cache', 'miss')
        ->assertSeeText($text);

    expect(Cache::get(cacheKey()))->toBe(['content' => $text, 'content_type' => 'application/javascript']);

    $this->get('/with-cache')
        ->assertOk()
        ->assertHeader('x-frontier-cache', 'hit')
        ->assertHeader('content-type', 'application/javascript')
        ->assertSeeText($text);

    Http::assertSentCount(1);
});

test('proxy caches each uri separately', function () {
    Http::fake([
        'frontier.test/*' => Http::response('Cached'),
    ]);

    $this->get('/with-cache/a')->assertOk();
    $this->get('/with-cache/b')->assertOk();
    $this->get('/with-cache/a')->assertHeader('x-frontier-cache', 'hit');

    Http::assertSentCount(2);
});

test('proxy cache expires after the configured ttl', function () {
    Http::fake([
        'frontier.test/*' => Http::response('Cached'),
    ]);

    $this->get('/with-cache')->assertOk();

    $this->travel(61)->seconds();

    $this->get('/with-cache')->assertHeader('x-frontier-cache', 'miss');

    Http::assertSentCount(2);
});

test('proxy does not add the cache header when cache is disabled', function () {
    Http::fake([
        'frontier.test/*' => Http::response('Plain'),
    ]);

    $this->get('/web')
        ->assertOk()
        ->assertHeaderMissing('x-frontier-cache');
});

test('proxy uses the configured cache store and ttl', function () {
    config([
        'cache.stores.none' => ['driver' => 'null'],
        'cache.default' => 'none',
    ]);

    Frontier::add([
        'enabled' => true,
        'type' => 'proxy',
        'host' => 'frontier.test',
        'cache_store' => 'array',
        'cache_ttl' => 10,
        'rules' => ['/store::cache'],
    ]);

    Http::fake([
        'frontier.test/*' => Http::response('Stored'),
    ]);

    $this->get('/store')->assertOk();

    expect(Cache::store('array')->has(cacheKey()))->toBeTrue();

    $this->travel(11)->seconds();

    $this->get('/store')->assertHeader('x-frontier-cache', 'miss');

    Http::assertSentCount(2);
});

test('proxy passing by middleware', function () {
    Http::fake([
        'frontier.test/*',
    ]);

    $this->getJson('/middleware')
        ->assertStatus(401);
});

test('proxy cache segment is kept when it is not the last segment', function () {
    Http::fake([
        'frontier.test/*' => Http::response('Cached'),
    ]);

    $this->get('/cache-first')->assertOk();
    $this->get('/cache-first')->assertOk()->assertContent('Cached');

    Http::assertSentCount(1);
});

test('proxy methods segment is kept when it is not the last segment', function () {
    Http::fake([
        'frontier.test/*' => Http::response('Replace'),
    ]);

    $this->post('/methods-first')
        ->assertContent('Done')
        ->assertOk();

    $this->put('/methods-first')
        ->assertMethodNotAllowed();
});

test('proxy forwards the upstream status code', function () {
    Http::fake([
        'frontier.test/web/missing' => Http::response('Not found', 404),
        'frontier.test/web/broken' => Http::response('Boom', 500),
    ]);

    $this->get('/web/missing')
        ->assertNotFound()
        ->assertContent('Not found');

    $this->get('/web/broken')
        ->assertStatus(500)
        ->assertContent('Boom');
});

test('proxy does not cache failed responses', function () {
    Http::fake([
        'frontier.test/*' => Http::response('Boom', 500),
    ]);

    $this->get('/with-cache')
        ->assertStatus(500)
        ->assertHeader('x-frontier-cache', 'miss');

    expect(Cache::has(cacheKey()))->toBeFalse();
});

test('proxy returns 504 when the host cannot be reached', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $this->get('/web')
        ->assertStatus(504);
});

test('proxy uses the default timeouts', function () {
    $route = Route::getRoutes()->match(HttpRequest::create('/web'));

    expect($route->defaults['config']['timeout'])->toBe(5)
        ->and($route->defaults['config']['connect_timeout'])->toBe(2);
});

test('proxy uses the configured timeouts', function () {
    Frontier::add([
        'enabled' => true,
        'type' => 'proxy',
        'host' => 'frontier.test',
        'timeout' => 10,
        'connect_timeout' => 3,
        'rules' => ['/slow'],
    ]);

    $route = Route::getRoutes()->match(HttpRequest::create('/slow'));

    expect($route->defaults['config']['timeout'])->toBe(10)
        ->and($route->defaults['config']['connect_timeout'])->toBe(3);
});

test('proxy serves the stale copy when the host answers a server error', function () {
    Http::fake([
        'frontier.test/*' => Http::sequence()
            ->push('Fresh', 200, ['Content-Type' => 'application/javascript'])
            ->push('Boom', 500),
    ]);

    $this->get('/with-cache')->assertOk();

    $this->travel(61)->seconds();

    $this->get('/with-cache')
        ->assertOk()
        ->assertContent('Fresh')
        ->assertHeader('content-type', 'application/javascript')
        ->assertHeader('x-frontier-cache', 'stale');

    Http::assertSentCount(2);
});

test('proxy serves the stale copy when the host cannot be reached', function () {
    $calls = 0;

    Http::fake(function () use (&$calls) {
        return ++$calls === 1
            ? Http::response('Fresh')
            : throw new ConnectionException('timeout');
    });

    $this->get('/with-cache')->assertOk();

    $this->travel(61)->seconds();

    $this->get('/with-cache')
        ->assertOk()
        ->assertContent('Fresh')
        ->assertHeader('x-frontier-cache', 'stale');
});

test('proxy stale copy expires after its own ttl', function () {
    Http::fake([
        'frontier.test/*' => Http::sequence()
            ->push('Fresh')
            ->push('Boom', 500),
    ]);

    $this->get('/with-cache')->assertOk();

    $this->travel(86401)->seconds();

    $this->get('/with-cache')->assertStatus(500);
});

test('proxy does not fall back to the stale copy on client errors', function () {
    Http::fake([
        'frontier.test/*' => Http::sequence()
            ->push('Fresh')
            ->push('Gone', 404),
    ]);

    $this->get('/with-cache')->assertOk();

    $this->travel(61)->seconds();

    $this->get('/with-cache')
        ->assertNotFound()
        ->assertContent('Gone');
});

test('proxy resolves the url at request time', function () {
    Frontier::resolveUrlUsing(function (string $url, HttpRequest $request, array $config) {
        expect($config['url'])->toBe('frontier.test/web');

        return str_replace('frontier.test', 'v2.frontier.test', $url) . '?tenant=' . $request->query('tenant');
    });

    Http::fake([
        'v2.frontier.test/*' => Http::response('Version 2'),
    ]);

    $this->get('/web/page?tenant=acme')
        ->assertOk()
        ->assertContent('Version 2');

    Http::assertSent(fn (Request $request) => $request->url() === 'v2.frontier.test/web/page?tenant=acme');
});

test('proxy caches each resolved url separately', function () {
    $version = 'v1';

    Frontier::resolveUrlUsing(function (string $url) use (&$version) {
        return str_replace('frontier.test', "$version.frontier.test", $url);
    });

    Http::fake([
        'v1.frontier.test/*' => Http::response('Version 1'),
        'v2.frontier.test/*' => Http::response('Version 2'),
    ]);

    $this->get('/with-cache')->assertContent('Version 1');

    $version = 'v2';

    $this->get('/with-cache')
        ->assertContent('Version 2')
        ->assertHeader('x-frontier-cache', 'miss');

    $version = 'v1';

    $this->get('/with-cache')
        ->assertContent('Version 1')
        ->assertHeader('x-frontier-cache', 'hit');

    Http::assertSentCount(2);
});
