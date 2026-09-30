# Contributing to predictflow/sdk (PHP)

This is a thin, typed client over the real PredictFlow API. The most valuable thing you can bring to a PR is confidence that what you wrote actually matches what the backend does.

## Ground rule: verify against the real API

Every method in this SDK should correspond to a real, working PredictFlow endpoint - method, path, and whether its parameters are query params or a JSON body (FastAPI endpoints mix both, inconsistently, depending on the specific route). Every resource method in this SDK was verified by actually calling the live backend during development, not just by reading the schema - do the same for anything you add or change: run it against a real (or local) PredictFlow instance, don't assume from what seems plausible.

If you're proposing a resource or method for a capability you *think* PredictFlow should have but haven't confirmed exists, open an issue first rather than a PR.

## Getting set up

```bash
git clone https://github.com/PredictFlow/php-sdk.git
cd php-sdk
composer install
```

```bash
composer test        # phpunit
composer stan          # phpstan analyse (level 8)
composer cs             # php-cs-fixer, dry-run
composer cs:fix          # php-cs-fixer, applies fixes
```

All three should be clean before you open a PR - CI runs them on PHP 8.1 through 8.4 and it's a required check.

## Making a change

1. Branch from `main`.
2. Keep the diff focused - one logical change per PR.
3. If you're adding or changing a resource method:
   - Match the existing pattern in `src/Resources/*.php` - a thin wrapper around `Http\HttpClient`, with `@return array<...>` PHPDoc annotations PHPStan can actually parse (see the note below - this has already bitten this SDK once).
   - Add a test in `tests/` using `MockPsr18Client` - assert the actual method/path/params sent, not just that the call doesn't throw.
   - Update the README's resource table if it's a new resource, or an example if it's a good illustration of something not yet covered.
4. **PHPDoc gotcha**: a one-line docblock like `/** Some description. @return array<string, mixed> */` is NOT parsed correctly by PHPStan when there's free text before the `@return` tag on the same line - it silently fails to pick up the return type. Put the description and the `@return` tag on separate lines:
   ```php
   /**
    * Some description.
    *
    * @return array<string, mixed>
    */
   ```
5. This package depends only on PSR-18/17 interfaces plus `php-http/discovery`, not a specific HTTP client implementation - keep it that way. `guzzlehttp/*` is a dev-only dependency (used for tests and examples).
6. Commit messages: a plain, present-tense description of what changed and why is enough. No enforced format.
7. Open the PR against `main` and fill in the template.

## Versioning & breaking changes

This package follows [Semantic Versioning](https://semver.org/), with the usual pre-1.0 caveat: anything in a `0.x` release can technically break between minor versions, but we still treat it like a real breaking-change bump (`0.1.x` → `0.2.0`), not a patch, once it's past initial development. Concretely, a **breaking change** is any of:

- Removing or renaming a public class, method, or exception.
- Changing a method's required parameters, or changing what an existing parameter means.
- Changing a response's array shape in a way that breaks a reasonable consumer (removing a key, changing a value's type) - adding a new key is not breaking.
- Changing default behavior a consumer could reasonably have been relying on (e.g. which HTTP methods retry by default).

Not breaking: internal refactors, dependency bumps, new resource methods, new optional parameters, README/example changes, adding a new exception subclass (as long as it still extends `PredictFlowException`, existing `catch` blocks keep working).

Every release gets a [CHANGELOG.md](CHANGELOG.md) entry - see the existing entry for the format. If your PR is user-facing, mention what changed under `## [Unreleased]` (add that heading at the top of the file if it doesn't exist yet) rather than leaving the changelog for the maintainer to reconstruct from commit messages at release time. Remember Packagist's version comes from the git tag, not from anything in `composer.json` (see "Releasing" below) - the changelog is the one place the human-readable version history actually lives.

## Review & merge

PRs require passing CI and a code owner review before merging - `main` is protected, so nobody merges without going through this, maintainers included.

## Releasing (maintainers)

Packagist works differently from npm/PyPI: there's no build-and-upload CI step. Packagist reads `composer.json` directly from this GitHub repo and picks up new versions from git tags (`vX.Y.Z` or `X.Y.Z`) via a webhook - as soon as a tag is pushed to `main`, it's live. There's no version field to keep in sync inside `composer.json` itself; Composer deliberately ignores one if present.

## Reporting a bug vs. a vulnerability

Regular bugs (wrong types, a broken example, a method that doesn't match the API) → open a GitHub issue.

Anything security-related → see [SECURITY.md](SECURITY.md) instead of a public issue.

## Code of conduct

Be respectful, assume good faith, keep disagreements about the code and not the person.
