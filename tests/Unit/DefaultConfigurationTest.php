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
