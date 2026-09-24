# Frontier

<a href="https://github.com/edersoares/frontier/actions"><img src="https://github.com/edersoares/frontier/actions/workflows/tests.yml/badge.svg" alt="Tests" /></a>
<a href="https://github.com/edersoares/frontier/blob/main/LICENSE"><img src="https://img.shields.io/github/license/edersoares/frontier" alt="License" /></a>

The frontier between your [Laravel](https://laravel.com/) app and your decoupled frontend.

**Frontier** allows you to run your favorite frontend framework and to serve the initial page by the backend, like a
proxy.

It's great for anyone using custom domains to test their applications or running multiple frontends that use the same
backend. You will be able to test your app using cookies, sessions and avoiding CORS "same source" issues.

## Installation

Just install `dex/frontier` into your Laravel app and configure some
[environment variables](https://github.com/edersoares/frontier#environment-variables).

```bash 
composer require dex/frontier
```

### Environment variables

You can configure your frontend using some environment variables described below.

| Variable                | Description                                                 | Default                   |
|-------------------------|-------------------------------------------------------------|---------------------------|
| `FRONTIER_TYPE`         | Define type of controller `http`, `proxy` or `view`         | `view`                    |
| `FRONTIER_ENDPOINT`     | Endpoint where the frontend will run                        | `frontier`                |
| `FRONTIER_VIEW`         | Default `view` that will be rendered or `url` of the server | `frontier::index`         |
| `FRONTIER_VIEWS_PATH`   | Directory where all the `views` are                         | `frontier/resources/html` |
| `FRONTIER_FIND`         | Content that will be replaced                               |                           |
| `FRONTIER_REPLACE_WITH` | Content that will be the replacement                        |                           |
| `FRONTIER_PROXY`        | URIs that you will do proxy                                 |                           |
| `FRONTIER_CACHE`        | When `http` type, indicates se cache will be do             | `true`                    |
| `FRONTIER_PROXY_HOST`   | `url` of the assets server                                  |                           |
| `FRONTIER_PROXY_RULES`  | Proxy rules                                                 |                           |
| `FRONTIER_PROXY_TIMEOUT` | Seconds to wait for the proxied host to respond            | `5`                       |
| `FRONTIER_PROXY_CONNECT_TIMEOUT` | Seconds to wait when connecting to the proxied host | `2`                      |
| `FRONTIER_PROXY_CACHE_STORE` | Cache store used by the `cache` rule segment, default store when empty |              |
| `FRONTIER_PROXY_CACHE_TTL` | Seconds a cached proxy response stays fresh                | `60`                      |
| `FRONTIER_PROXY_CACHE_STALE_TTL` | Seconds the last good response is kept to serve when the host fails | `86400`        |

### Frontend types

You can use 3 different types of frontend `http`, `proxy` or `view`.

#### HTTP

Use in `FRONTIER_VIEW` the URL of your frontend server.

#### Proxy

Use in `FRONTIER_PROXY_HOST` or `FRONTIER_VIEW` the URL of your frontend server.

> `FRONTIER_VIEW` will be removed in the future.

`FRONTIER_PROXY_RULES` is a list of rules separated by `|`. Each rule starts with the URI to proxy, followed by
optional segments separated by `::`.

| Segment                     | Description                                                                |
|-----------------------------|----------------------------------------------------------------------------|
| `exact`                     | Proxy only this URI, not everything under it                               |
| `cache`                     | Cache successful `GET` responses                                           |
| `methods(get,post,...)`     | HTTP methods accepted by the route, `GET` by default                       |
| `middleware(name)`          | Middleware applied to the route, repeatable                                |
| `replace(search,replace)`   | Replace text in the response body, `replace` defaults to the proxied URL   |
| `rewrite(search,replace)`   | Rewrite the URL requested from the host                                    |

The status code returned by the host is forwarded to the client, so a `404` or `500` from your frontend server is
seen as such by the browser. Failed responses are never cached.

When the host cannot be reached within `FRONTIER_PROXY_CONNECT_TIMEOUT` or does not answer within
`FRONTIER_PROXY_TIMEOUT`, the proxy responds with `504 Gateway Timeout` instead of holding the PHP worker.

##### Cache

Rules with the `cache` segment store successful `GET` responses in the Laravel cache, using the store defined by
`FRONTIER_PROXY_CACHE_STORE` and a TTL of `FRONTIER_PROXY_CACHE_TTL` seconds. The key is derived from the final
URL requested from the host, so every URI is cached on its own and the cache is shared by all the servers that
share the store.

Every successful response is also kept as a stale copy for `FRONTIER_PROXY_CACHE_STALE_TTL` seconds. When the
fresh copy has expired and the host answers a `5xx` or cannot be reached, the stale copy is served with status
`200` so the frontend keeps working while the host is down. Client errors such as `404` are forwarded as is.

Responses carry an `X-Frontier-Cache` header with `hit`, `miss` or `stale`, which is handy to check with `curl -I`.
To invalidate everything at once run `php artisan cache:clear`, or point `FRONTIER_PROXY_CACHE_STORE` to a
dedicated store so it can be flushed without touching the rest of the application cache.

#### View

Use in `FRONTIER_VIEW` the name of your view that you initialize your frontend, this is relative a Blade views.

### Examples

#### Vite and Vue.js

When using [Vite](https://vitejs.dev/) and [Vue.js](https://vuejs.org/) you can start your project with these
environment variables using `http` approach.

```bash
FRONTIER_ENDPOINT=/vue
FRONTIER_TYPE=http
FRONTIER_VIEW=http://localhost:5173/
FRONTIER_FIND=/@vite/client,/src/main.ts,/vite.svg
FRONTIER_REPLACE_WITH=http://localhost:5173/@vite/client,http://localhost:5173/src/main.ts,http://localhost:5173/vite.svg
FRONTIER_PROXY=/vite.svg
FRONTIER_CACHE=false
```

#### Nuxt.js

When using [Nuxt](https://nuxt.com/) you can start your project with these environment variables using `proxy` approach.

```bash
FRONTIER_PROXY_HOST=http://localhost:3000
FRONTIER_PROXY_RULES=/_vfs.json::exact|/favicon.ico::exact::rewrite(/favicon.ico)|/__nuxt_devtools__/client/_nuxt/builds/meta|/__nuxt_devtools__/client::replace(/__nuxt_devtools__/client/_nuxt/)|/_nuxt|/_fonts|/::replace(/_nuxt/)
```

### Multiple frontends

You can run multiple frontends, just create a custom configuration file.

```bash 
php artisan vendor:publish --tag=frontier
```

The `config/frontier.php` file will be created in your Laravel app. This file contains some settings that can be
replicated to add more frontends to your app.

## License

[Frontier](https://github.com/edersoares/frontier) is licensed under the MIT license.
See the [license](https://github.com/edersoares/frontier/blob/main/LICENSE) file for more details.
