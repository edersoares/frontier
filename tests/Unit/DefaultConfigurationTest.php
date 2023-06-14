<?php

test('when FRONTIER_PROXY is not informed `proxy` key should be empty', function () {
    $config = config('frontier.frontier.proxy');

    expect($config)->toBeEmpty();
});
