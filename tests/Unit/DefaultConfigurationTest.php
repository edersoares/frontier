<?php

declare(strict_types=1);

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

test('proxy header lists default to the safe sets', function () {
    expect(config('frontier.proxy.request_headers'))->toBe('accept,accept-language,user-agent')
        ->and(config('frontier.proxy.response_headers'))->toBe('content-type,cache-control,etag,last-modified,content-disposition')
        ->and(config('frontier.frontier.request_headers'))->toBe('accept,accept-language,user-agent');
});

test('proxy cache stores responses up to 1 MB by default', function () {
    expect(config('frontier.proxy.cache_max_size'))->toBe(1048576)
        ->and(config('frontier.frontier.cache_max_size'))->toBe(1048576);
});
