<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use Illuminate\Contracts\View\Factory as View;

class FrontendViewController
{
    public function __construct(
        private View $view
    ) {
    }

    public function __invoke($uri, $config): string
    {
        $content = $this->view->make($config['view'])->render();

        return str_replace(
            array_keys($config['replaces'] ?? []),
            array_values($config['replaces'] ?? []),
            $content
        );
    }
}
