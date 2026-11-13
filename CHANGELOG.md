# Changelog

## 2.0.1

- A GraphQL object with keys counts as data, so a category `urlResolver` answering `{sku: null, type: CATEGORY, id: 6}` no longer makes the whole response a 404. The first-element rule applies to lists only.

## 2.0.0

Forked from `scandipwa/cache` 1.1.5. Module name and namespace are unchanged, and the package replaces `scandipwa/cache` at every version, so it installs as a drop-in replacement.

- Magento 2.4.9 and PHP 8.3 support.
- Tagging and 404 handling now run on the persisted-query path, where neither plugin had ever executed.
- A response is a 404 only when every top-level field resolved to nothing, so the status no longer depends on field order.
- Validation and authorisation errors keep the status core set; only `graphql-no-such-entity` becomes a 404.
- Core's per-resolver cache identities are merged into `X-Magento-Tags`, so resolver-cache hits stay tagged.
- No `X-Magento-Tags` for a query a resolver declares uncacheable.
- CMS block collections are tagged, and a CMS block save purges Varnish the way a page save already did.
- Varnish purge sends one anchored pattern per identity instead of a single comma-joined string.
- A response that carries no identity from either source never enters the shared pool: it answers no-cache with no `X-Pool`, as core's own rule is.
