<?php

namespace Dex\Laravel\Frontier;

use Illuminate\Contracts\View\Factory as View;

class FrontendController
{
    protected $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function __invoke($uri, $config)
    {
        $content = $this->view->make($config['view'])->render();

        return str_replace(
            array_keys($config['replaces'] ?? []),
            array_values($config['replaces'] ?? []),
            $content
        );
    }
}
