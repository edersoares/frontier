<?php

declare(strict_types=1);

test('when `FRONTIER_PROXY` is not informed `proxy` key should be empty', function () {
    $config = config('frontier.frontier.proxy');

    expect($config)->toBeEmpty();
});

test('when `FRONTIER_FIND` is not informed `replaces` key should be empty', function () {
    $config = config('frontier.frontier.replaces');

    expect($config)->toBeEmpty();
});

test('when `FRONTIER_REPLACE_WITH` is not informed `replaces` key should be empty', function () {
    $config = config('frontier.frontier.replaces');

    expect($config)->toBeEmpty();
});

test('proxy timeouts default to 5 and 2 seconds', function () {
    expect(config('frontier.proxy.timeout'))->toBe(5)
        ->and(config('frontier.proxy.connect_timeout'))->toBe(2)
        ->and(config('frontier.frontier.timeout'))->toBe(5)
        ->and(config('frontier.frontier.connect_timeout'))->toBe(2);
});

test('proxy cache uses the default store with a ttl of 60 seconds', function () {
    expect(config('frontier.proxy.cache_store'))->toBeNull()
        ->and(config('frontier.proxy.cache_ttl'))->toBe(60)
        ->and(config('frontier.frontier.cache_store'))->toBeNull()
        ->and(config('frontier.frontier.cache_ttl'))->toBe(60)
        ->and(config('frontier.proxy.cache_stale_ttl'))->toBe(86400)
        ->and(config('frontier.frontier.cache_stale_ttl'))->toBe(86400);
});
