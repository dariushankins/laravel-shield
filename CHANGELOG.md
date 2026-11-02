# Changelog

## 10.0.0 — independent Laravel 13 port

- Require PHP 8.4+, Laravel 13 components, and Symfony HTTP Kernel 7.4 or 8.x.
- Upgrade tests to Orchestra Testbench 11 and PHPUnit 11.5.
- Replace the implicitly nullable `getUsers()` parameter with an explicit nullable type.
- Type the middleware request and keep its existing mixed response contract.
- Merge configuration during service provider registration; add `shield-config` publication tag.
- Use a scoped container binding; clear the current user at the start of each verification.
- Return null from `getCurrentUser()` when no user is authenticated.
- Reject malformed credential entries without a runtime exception.
- Preserve package discovery, the original namespace, hashed credential configuration,
  container alias, named user restrictions, and HTTP Basic challenge.
- Add HTTP integration, configuration, lifecycle, and invalid-credential regression tests.
- Document Laravel 13's `bootstrap/app.php` middleware registration and local installation.
