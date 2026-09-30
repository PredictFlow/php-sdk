# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); this project follows
[Semantic Versioning](https://semver.org/) once it reaches 1.0.0 - see
[CONTRIBUTING.md](CONTRIBUTING.md#versioning--breaking-changes) for what that
means before then.

## [0.1.0] - 2026-09-30

Initial release.

### Added

- `PredictFlow` client covering 8 resources: stores, products, predictions,
  analytics, pricing, scenarios, alerts, exports.
- Built against PSR-18 (`ClientInterface`) + PSR-17 (factories) instead of a
  specific HTTP client - `php-http/discovery` finds an installed one
  automatically, or one can be injected (useful inside WordPress/WooCommerce
  plugins that already have their own HTTP stack).
- Typed exception hierarchy (`PredictFlowException` and subclasses),
  including unwrapping FastAPI/Pydantic's array-shaped `detail` on 422s into
  a readable message.
- Automatic retries with backoff on `GET`/`PUT`/`DELETE` for `429`/5xx
  responses; `POST`/`PATCH` don't retry by default since they aren't
  guaranteed idempotent (a caller can opt a specific call in via
  `maxRetries`).
- `Http\FileDownload` for the exports resource - raw bytes + filename, not
  a failed JSON decode of a spreadsheet.
- PHPStan level 8 clean, PSR-12 + `strict_types` throughout.

Every endpoint this SDK calls was verified against the real backend before
release (see [CONTRIBUTING.md](CONTRIBUTING.md)) - including a live smoke
test against a running PredictFlow instance (real store creation, CSV
upload, a genuine validation error round-tripped end to end), not just
against the OpenAPI schema.
