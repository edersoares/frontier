<?php

namespace Dex\Laravel\Frontier;

class Frontier
{
    private static Frontier $instance;

    private array $config = [];

    public static function instance(): static
    {
        if (empty(static::$instance)) {
            static::reset();
        }

        return static::$instance;
    }

    public static function reset(): void
    {
        static::$instance = new static();
    }

    public function config(): array
    {
        $config = [];

        foreach ($this->config as $item) {
            $config = array_merge($config, $item->config());
        }

        return $config;
    }

    public function push(FrontierConfig $config): void
    {
        $this->config[] = $config;
    }

    public static function http(string $name, string $endpoint, string $url): FrontierConfigHttp
    {
        $config = new FrontierConfigHttp($name, $endpoint, $url);

        Frontier::instance()->push($config);

        return $config;
    }

    public static function view(string $name, string $endpoint, string $view): FrontierConfigView
    {
        $config = new FrontierConfigView($name, $endpoint, $view);

        Frontier::instance()->push($config);

        return $config;
    }
}
