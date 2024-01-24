<?php

namespace Dex\Laravel\Frontier;

class FrontierConfigView extends FrontierConfig
{
    protected array $views = [];

    protected array $publishes = [];

    public function __construct(string $name, string $endpoint, string $view)
    {
        parent::__construct($name, 'view', $endpoint, $view);
    }

    public function views(string $namespace, string $path): static
    {
        $this->views[$namespace] = $path;

        return $this;
    }

    public function publish(string $namespace, string $from, string $to): static
    {
        $this->publishes[$namespace][$from] = $to;

        return $this;
    }

    public function config(): array
    {
        return array_merge_recursive(parent::config(), [
            $this->name => [
                'views' => $this->views,
                'publishes' => $this->publishes,
            ],
        ]);
    }
}
