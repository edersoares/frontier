<?php

namespace Dex\Laravel\Frontier;

class FrontierConfigHttp extends FrontierConfig
{
    protected array $proxy = [];

    protected bool $cache = true;

    public function __construct(string $name, string $endpoint, string $url)
    {
        parent::__construct($name, 'http', $endpoint, $url);
    }

    public function cache(): static
    {
        $this->cache = true;

        return $this;
    }

    public function noCache(): static
    {
        $this->cache = false;

        return $this;
    }

    public function proxy(string $proxy): static
    {
        $this->proxy[] = $proxy;

        return $this;
    }

    public function replaceAsProxy(string $uri): static
    {
        return $this->replace($uri, $this->view . $uri);
    }

    public function config(): array
    {
        return array_merge_recursive(parent::config(), [
            $this->name => [
                'proxy' => $this->proxy,
                'cache' => $this->cache,
                'headers' => [],
            ],
        ]);
    }
}
