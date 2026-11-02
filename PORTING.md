# Port provenance and verification

## Source

- Upstream: https://github.com/vinkla/laravel-shield
- Source branch: `master`
- Source commit: `f0f44e5b1e49b91c6239142325d350687c38d14f`
- Port branch: `laravel13`
- Port release identifier: `10.0.0` (independent release, not published upstream)
- License: MIT; original license and copyright retained.

## Verification performed

The complete suite passed in all four combinations below. Each run executed
**28 tests and 76 assertions**, without PHP or PHPUnit deprecation notices.

| PHP runtime | Dependency selection | Laravel | Testbench | PHPUnit | HTTP Kernel |
| --- | --- | --- | --- | --- | --- |
| 8.4.23 | Highest stable | 13.39.0 | 11.3.0 | 11.5.57 | 8.1.10 |
| 8.5.8 | Highest stable | 13.39.0 | 11.3.0 | 11.5.57 | 8.1.10 |
| 8.4.23 | Lowest permitted stable | 13.30.0 | 11.0.0 | 11.5.50 | 7.4.12 |
| 8.5.8 | Lowest permitted stable | 13.30.0 | 11.0.0 | 11.5.50 | 7.4.12 |

The lowest selection reflects the versions Composer can currently resolve while
respecting its security advisory checks and the full test-tool constraints.
Individual earlier Laravel 13 patch releases were not separately executed.

Additional checks passed:

- `composer validate --strict`.
- `composer check-platform-reqs` on PHP 8.4.
- PHP syntax checks on every source, config, and test PHP file.
- PER-CS style check and `git diff --check`.
- Composer's dependency updates reported no security vulnerability advisories.

A separate minimal Laravel 13.39 application verified the package before its
Composer-name and namespace change, installing it as `vinkla/shield:10.0.0`
through the path repository with the normal Laravel framework dependency and no
Testbench dependency. It passed these checks:

1. Composer package discovery lists the Shield provider.
2. Laravel automatically registers and boots that provider.
3. A protected route rejects missing credentials with HTTP 401.
4. That rejection includes `WWW-Authenticate: Basic`.
5. Valid configured hashed credentials return HTTP 200.
6. The route receives the expected authenticated user key.
7. `vendor:publish --tag=shield-config` succeeds.
8. The published config file exists.
9. `config:cache` succeeds and writes Laravel's configuration cache.

## Authentication changes

Hashed username/password verification and the `shield:<user>` restriction remain
in place. Missing credentials or malformed two-element credential entries deny
access. A failed verification clears a previous successful identity. A new
container scope receives a new Shield instance.

`getCurrentUser()` returns null when unauthenticated. Callers or subclasses that
assumed an always-present string should account for that change. This port
removes the legacy Lumen-specific provider setup and targets Laravel 13 only.

## Contents

The archive includes full package source, config, original license, tests,
Composer metadata, CI workflows, README installation instructions, changelog,
and this verification record. `UPSTREAM.patch` contains the corresponding diff
against the upstream source commit. Dependencies, local PHP runtimes, secrets,
and Git metadata are excluded.
