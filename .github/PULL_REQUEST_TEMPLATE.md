## Summary

<!-- What does this PR change, and why? -->

## Type of change

- [ ] Bug fix
- [ ] New feature / new resource or method
- [ ] Breaking change (anything that changes an existing public method's signature or behavior)
- [ ] Docs only

## Verified against the real API

<!--
This SDK wraps the real PredictFlow API - every endpoint it calls should
correspond to a real one, including whether its parameters are query
params or a JSON body. Every method was verified by calling the live
backend during initial development, not just by reading the schema.
-->

- [ ] Every new/changed HTTP call matches a real backend endpoint (method, path, query-vs-body, and response shape)
- [ ] No new required runtime dependency was introduced without a prior issue discussing it (this SDK depends only on PSR-18/17 interfaces + `php-http/discovery`, not a specific HTTP client)

## Test plan

- [ ] `composer cs` passes
- [ ] `composer stan` passes (PHPStan level 8)
- [ ] `composer test` passes, and I added/updated tests covering this change

## Linked issue

Closes #
