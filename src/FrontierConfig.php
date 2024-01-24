<?php

namespace Dex\Laravel\Frontier;

class FrontierConfig
{
    protected string $name;

    protected string $type;

    protected string $endpoint;

    protected string $view;

    protected array $middleware = [];

    protected array $replaces = [];

    public function __construct(string $name, string $type, string $endpoint, string $view)
    {
        $this->name = $name;
        $this->type = $type;
        $this->endpoint = $endpoint;
        $this->view = $view;
    }

    public function middleware(string $middleware): static
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    public function replace(string $find, string $replace): static
    {
        $this->replaces[$find] = $replace;

        return $this;
    }

    public function config(): array
    {
        return [
            $this->name => [
                'type' => $this->type,
                'endpoint' => $this->endpoint,
                'view' => $this->view,
                'middleware' => $this->middleware,
                'replaces' => $this->replaces,
            ],
        ];
    }
}
