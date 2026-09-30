<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use Illuminate\Contracts\View\Factory as View;

/**
 * @internal
 */
final class FrontendViewController
{
    public function __construct(
        private View $view
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function __invoke(string $uri, array $config): string
    {
        $content = $this->view->make($config['view'])->render();

        return str_replace(
            array_keys($config['replaces'] ?? []),
            array_values($config['replaces'] ?? []),
            $content
        );
    }
}
