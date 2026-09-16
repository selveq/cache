# ScandiPWA Cache

Fork of [scandipwa/cache](https://github.com/scandipwa/cache) 1.1.5, maintained by Selveq for Magento 2.4.9 and PHP 8.3. Module name and namespace are unchanged, and the package replaces `scandipwa/cache` at every version, so it installs as a drop-in replacement. Selveq is not affiliated with or endorsed by Scandiweb.

## What it does

- Emits one `X-Magento-Tags` header per persisted query, from the identities of every entity the request loaded and from core's per-resolver cache identities.
- Answers `404` with `max-age=0` when a query resolved nothing, so an empty answer is never cached.
- Emits no tags when a resolver declares the query uncacheable.
- Purges Varnish on CMS page, CMS block, menu and menu item saves.

## Install

```sh
composer require selveq/cache
bin/magento setup:upgrade
```

## License

[OSL-3.0](LICENSE), the license of the original work. Scandiweb's copyright notices are kept in every file, and each file Selveq changed carries a `Modifications © Selveq` notice.
