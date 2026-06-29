<?php

declare(strict_types=1);

namespace Dex\Laravel\Frontier;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Session\Middleware\StartSession;

class AllowedFrontendsMiddleware
{
    public function handle($request, $next)
    {
        $domain = $this->domain($request);

        if ($this->isAllowedFromDomain($domain)) {
            return $this->run($domain, $request, $next);
        }

        return $next($request);
    }

    protected function run($domain, $request, $next)
    {
        $this->configureCorsAndSession($domain);

        return $this->pipeline()->send($request)->through(
            $this->frontendMiddleware()
        )->then(function ($request) use ($next) {
            return $next($request);
        });
    }

    protected function pipeline(): Pipeline
    {
        return new Pipeline(app());
    }

    protected function configureCorsAndSession(string $domain): void
    {
        config([
            'session.http_only' => true,
            'session.secure' => true,
            'session.same_site' => 'none',
            'cors.allowed_origins' => [$domain],
            'cors.supports_credentials' => true,
        ]);
    }

    protected function frontendMiddleware(): array
    {
        return [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            PreventRequestForgery::class,
        ];
    }

    protected function domain(Request $request): string
    {
        $domain = $request->headers->get('referer') ?: $request->headers->get('origin', '');

        return trim($domain, '/');
    }

    protected function isAllowedFromDomain(?string $domain): bool
    {
        if (empty($domain)) {
            return false;
        }

        $stateful = str(config('frontier.frontend', ''))
            ->explode(',')
            ->filter()
            ->map(fn ($uri) => str($uri)->trim()->replace(['https://', 'http://'], '')->append('/*')->value())
            ->all();

        return str($domain)
            ->replaceFirst('https://', '')
            ->replaceFirst('http://', '')
            ->trim('')
            ->append('/')
            ->is($stateful);
    }
}
