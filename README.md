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

| Variable | Description | Default |
| --- | --- |---------------------------|
| `FRONTIER_ENDPOINT` | Endpoint where the frontend will run | `frontier` |
| `FRONTIER_VIEW` | Default `view` that will be rendered | `frontier::index` |
| `FRONTIER_VIEWS_PATH` | Directory where all the `views` are | `frontier/resources/html` |
| `FRONTIER_VIEWS_PATH_HTTP` | Allow get all the `views` from a CDN or remote URL |  |
| `FRONTIER_FIND` | Content that will be replaced in the `view` |  |
| `FRONTIER_REPLACE_WITH` | Content that will be the replacement |  |

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
