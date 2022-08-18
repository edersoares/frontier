<?php

namespace Dex\Laravel\Frontier;

use Illuminate\Contracts\View\Factory as View;
use GuzzleHttp\Client;

class FrontendController
{
    protected $view;

    public function __construct(View $view, Client $http)
    {
        $this->view = $view;
        $this->http = $http;
    }

    public function __invoke($uri, $config)
    {
        $content = $this->view->make($config['view'])->render();

        if ($config['http']) {
            $content = $this->http->get($config['http'], [
                'headers' => $config['headers'],
            ])->getBody()->getContents();
        }

        return str_replace(
            array_keys($config['replaces'] ?? []),
            array_values($config['replaces'] ?? []),
            $content
        );
    }
}
