# Security Policy

## Supported versions

This package is pre-1.0 and moving fast. Only the **latest published version on Packagist** is supported with security fixes.

## Reporting a vulnerability

**Please do not open a public GitHub issue for a security vulnerability.**

Use GitHub's private vulnerability reporting instead:

1. Go to the [Security tab](https://github.com/PredictFlow/php-sdk/security) of this repository.
2. Click **Report a vulnerability**.
3. Describe the issue, including steps to reproduce and, if you have one, a suggested fix.

This opens a private advisory visible only to maintainers and you - it does not create a public issue.

We'll acknowledge a report within 5 business days and aim to have a fix (or a clear remediation plan) within 30 days for confirmed vulnerabilities, sooner for anything actively exploitable.

## What counts as a security issue here

This SDK is a thin HTTP client - its attack surface is narrow, but a few things are genuinely security-relevant:

- Anything that could leak an API key (logging headers, including credentials in an exception message, sending the key somewhere it shouldn't go).
- A way to make a request bypass the `Authorization` header entirely and still succeed.
- A retry behavior that could replay a request with stale/wrong credentials, or send one request's data to the wrong recipient.
- The multipart body builder (`Http\MultipartBuilder`) mishandling untrusted filenames/content in a way that could corrupt the request boundary or inject headers.

Things that are **not** security issues, just bugs: a wrong type hint, a method that calls the wrong endpoint, a broken example. Please file those as regular issues instead.

## What we do on our end

- Secret scanning and push protection are enabled on this repository.
- Dependabot security updates are enabled.
- `main` is a protected branch - every change goes through CI and a code owner review before merging, including changes from maintainers.
