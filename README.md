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

### Frontend types

You can use 3 different types of frontend `http`, `proxy` or `view`.

#### HTTP

Use in `FRONTIER_VIEW` the URL of your frontend server.

#### Proxy

Use in `FRONTIER_VIEW` the URL of your real server.

#### View

Use in `FRONTIER_VIEW` the name of your view that you initialize your frontend, this is relative a Blade views.

### Examples

#### Vite and Vue.js

When using [Vite](https://vitejs.dev/) and [Vue.js](https://vuejs.org/) you can start your project with these
environment variables.

```bash
FRONTIER_ENDPOINT=/vue
FRONTIER_TYPE=http
FRONTIER_VIEW=http://localhost:5173/
FRONTIER_FIND=/@vite/client,/src/main.ts,/vite.svg
FRONTIER_REPLACE_WITH=http://localhost:5173/@vite/client,http://localhost:5173/src/main.ts,http://localhost:5173/vite.svg
FRONTIER_PROXY=/vite.svg
FRONTIER_CACHE=false
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
