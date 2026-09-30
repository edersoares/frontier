<?php

declare(strict_types=1);

use Dex\Laravel\Frontier\Frontier;
use Dex\Laravel\Frontier\ProxyRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

afterEach(fn () => Frontier::flush());

test('a disabled frontend registers nothing even when invalid', function () {
    $routes = Route::getRoutes()->count();

    Frontier::add(['enabled' => false, 'type' => 'nope']);

    expect(Route::getRoutes()->count())->toBe($routes);
});

test('an unknown type throws', function () {
    Frontier::add(['enabled' => true, 'type' => 'nope']);
})->throws(InvalidArgumentException::class, 'Unknown Frontier type [nope]');

test('a missing type throws', function () {
    Frontier::add(['enabled' => true]);
})->throws(InvalidArgumentException::class, 'Unknown Frontier type [NULL]');

test('a view frontend without endpoint throws', function () {
    Frontier::add(['enabled' => true, 'type' => 'view', 'view' => 'frontier::index']);
})->throws(InvalidArgumentException::class, 'The `endpoint` of a `view` frontend is required.');

test('a view frontend without view throws', function () {
    Frontier::add(['enabled' => true, 'type' => 'view', 'endpoint' => 'app', 'view' => '']);
})->throws(InvalidArgumentException::class, 'The `view` of a `view` frontend is required.');

test('a proxy with rules and without host throws', function () {
    Frontier::add(['enabled' => true, 'type' => 'proxy', 'rules' => ['/new']]);
})->throws(InvalidArgumentException::class, 'The proxy `host` is required when there are rules.');

test('a proxy without rules registers nothing', function () {
    $routes = Route::getRoutes()->count();

    Frontier::add(['enabled' => true, 'type' => 'proxy']);

    expect(Route::getRoutes()->count())->toBe($routes);
});

test('a proxy with rules that are not an array throws', function () {
    Frontier::add(['enabled' => true, 'type' => 'proxy', 'host' => 'https://cdn.test', 'rules' => '/new']);
})->throws(InvalidArgumentException::class, 'The proxy `rules` must be an array of rule strings.');

test('a rule with an unknown segment throws', function () {
    ProxyRule::fromString('/new::chace');
})->throws(InvalidArgumentException::class, 'Unknown segment [chace] in the proxy rule [/new::chace]');

test('a rule without uri throws', function () {
    ProxyRule::fromString('::cache');
})->throws(InvalidArgumentException::class, 'The proxy rule [::cache] must start with the URI to proxy.');

test('flush forgets the url resolver', function () {
    Frontier::resolveUrlUsing(fn (string $url) => 'resolved');

    expect(Frontier::resolveUrl('original', Request::create('/'), []))->toBe('resolved');

    Frontier::flush();

    expect(Frontier::resolveUrl('original', Request::create('/'), []))->toBe('original');
});
