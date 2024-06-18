<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier\Tests;

use Dex\Laravel\Frontier\Frontier;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => Frontier::add([
    'type' => 'proxy',
    'host' => 'frontier.test',
    'rules' => [
        '/favicon.ico::exact',
        '/exact/replace::exact::replace(/exact/replace,https://frontier.test/another/exact/replace)',
        '/replace::replace(/replace)',
        '/all::replace(Replace,is amazing!)',
        '/web',
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
