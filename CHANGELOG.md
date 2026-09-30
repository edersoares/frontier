# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Deprecated

- The `http` type. Registering an `http` frontend triggers an `E_USER_DEPRECATED` notice. Use the `proxy` type with `rewrite` and `replace` segments instead; the README shows the Vite example migrated.
- Falling back to `FRONTIER_VIEW` as the proxy host. Set `FRONTIER_PROXY_HOST`.

### Added

- `FRONTIER_PROXY_REQUEST_HEADERS` and `FRONTIER_PROXY_RESPONSE_HEADERS` define which headers the proxy forwards in each direction. Defaults: `accept,accept-language,user-agent` to the host and `content-type,cache-control,etag,last-modified,content-disposition` back to the client. Before, only `Accept` went to the host and only `Content-Type` came back.

- `Frontier::add()` validates the config and throws `InvalidArgumentException` for an unknown `type`, a missing `endpoint` or `view`, or a proxy without `host`. Proxy rules with an unknown segment or without a URI throw as well instead of being silently ignored.
- `Frontier::flush()` forgets the URL resolver, handy in tests and long-running workers.
- The proxy accepts `OPTIONS` requests when listed in `methods(...)`.

- The `proxy` type forwards the status code returned by the host, so a `404` or `500` from the frontend server is seen as such by the browser.
- `FRONTIER_PROXY_TIMEOUT` and `FRONTIER_PROXY_CONNECT_TIMEOUT` define explicit timeouts for the proxied request. When the host cannot be reached the proxy answers `504 Gateway Timeout`.
- Rules with the `cache` segment store successful `GET` responses in the Laravel cache, configured by `FRONTIER_PROXY_CACHE_STORE` and `FRONTIER_PROXY_CACHE_TTL`. Responses carry an `X-Frontier-Cache` header with `hit`, `miss` or `stale`.
- The last good response is kept for `FRONTIER_PROXY_CACHE_STALE_TTL` seconds and served when the host answers a `5xx` or cannot be reached.
- `Frontier::resolveUrlUsing()` registers a callback to resolve the proxied URL per request.
- The query string is forwarded to the proxied host.

### Changed

- The controllers are `final` and marked `@internal`. The public API is `Frontier::add()`, `Frontier::addFromConfig()`, `Frontier::resolveUrlUsing()`, `Frontier::flush()`, the config keys, the environment variables and the rule syntax.

- The `proxy` type no longer answers `200` for every response from the host.
- The `proxy` type no longer caches responses in files under `storage/framework/views`. The `http` type keeps the file cache.
- The path requested from the host mirrors the path requested by the browser, including the trailing slash.

### Fixed

- The `cache` and `methods(...)` segments are applied regardless of their position in the rule.

### Removed

- The `FRONTIER_PROXY` environment variable was removed from the documentation. It was not read by the package.

## [0.18.0] - 2026-08-24

See the [release notes](https://github.com/edersoares/frontier/releases/tag/0.18.0).

[Unreleased]: https://github.com/edersoares/frontier/compare/0.18.0...HEAD
[0.18.0]: https://github.com/edersoares/frontier/releases/tag/0.18.0
