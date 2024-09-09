<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier\Tests;

use Dex\Laravel\Frontier\Frontier;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => Frontier::add([
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
    ],
]));

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
    $file = storage_path('framework/views/frontier-GET-frontier-test-with-cache');
    $text = 'Frontier by HTTP';

    Http::fake([
        'frontier.test/*' => Http::response($text),
    ]);

    $this->assertFileDoesNotExist($file);

    $this->get('/with-cache')
        ->assertStatus(200)
        ->assertSeeText($text);

    $this->assertFileExists($file);
    $this->assertStringEqualsFile($file, $text);

    $this->get('/with-cache')
        ->assertStatus(200)
        ->assertSeeText($text);

    // Remove cache
    $this->artisan('view:clear');
});
