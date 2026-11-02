# Laravel Shield — Laravel 13 port

HTTP Basic authentication middleware using configured, hashed credentials. This
port targets **Laravel 13** and **PHP 8.4 or later within PHP 8.x**. It uses the
`dariushankins/shield` Composer name and `Dariushankins\Shield` namespace, while
retaining the configuration keys and named-user middleware syntax.

The original [vinkla/laravel-shield](https://github.com/vinkla/laravel-shield)
repository is archived. This is an independent compatibility port, not an
upstream release. The original MIT license and copyright are retained.

## Install this downloaded port

Extract the archive in your Laravel application directory. It contains one
`laravel-shield/` directory. Move that directory into `packages/`:

```bash
mkdir -p packages
mv laravel-shield packages/laravel-shield
composer config repositories.laravel-shield '{"type":"path","url":"packages/laravel-shield","options":{"symlink":false,"versions":{"dariushankins/shield":"10.0.0"}}}'
composer require 'dariushankins/shield:^10.0' --with-all-dependencies
php artisan vendor:publish --tag=shield-config
```

The path repository above installs **this port**. Keep the repository
configuration and deploy `packages/laravel-shield/` with your application so
future Composer installs can still find it. Do not edit `vendor/` directly.

The service provider is registered through Laravel package discovery. If your
application disables discovery, add `Dariushankins\Shield\ShieldServiceProvider::class`
to `bootstrap/providers.php`.

## Configure credentials

Both the username and password must be **password hashes**, not plaintext. For
example, in `php artisan tinker`:

```php
password_hash('your-username', PASSWORD_BCRYPT);
password_hash('your-password', PASSWORD_BCRYPT);
```

Copy the two resulting hashes into `.env`, using single quotes to preserve the
hash characters:

```dotenv
SHIELD_USER='$2y$...your-username-hash...'
SHIELD_PASSWORD='$2y$...your-password-hash...'
```

The published `config/shield.php` reads these values. Additional named users
can be configured with the same two-element structure:

```php
'users' => [
    'default' => [env('SHIELD_USER'), env('SHIELD_PASSWORD')],
    'admin' => [env('SHIELD_ADMIN_USER'), env('SHIELD_ADMIN_PASSWORD')],
],
```

After changing configuration on a server that caches it, run:

```bash
php artisan config:cache
```

Missing or malformed hashes deny access with HTTP 401 instead of causing a
TypeError. Shield does not authenticate database users or create a login session.
Use HTTPS when sending Basic authentication credentials.

## Register middleware in Laravel 13

Add the alias to your existing `withMiddleware` callback in `bootstrap/app.php`:

```php
use Illuminate\Foundation\Configuration\Middleware;
use Dariushankins\Shield\ShieldMiddleware;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'shield' => ShieldMiddleware::class,
    ]);
})
```

Protect a route using any configured user, or restrict it to one named entry:

```php
use Illuminate\Support\Facades\Route;

Route::get('/preview', fn () => 'Protected')->middleware('shield');
Route::get('/admin-preview', fn () => 'Admin only')->middleware('shield:admin');
```

Alternatively, attach `Dariushankins\Shield\ShieldMiddleware::class` directly to a
route without registering an alias.

Rejected requests return HTTP 401 and the `WWW-Authenticate: Basic` challenge.

## Access the authenticated entry

```php
use Dariushankins\Shield\Shield;

$user = app(Shield::class)->getCurrentUser(); // e.g. 'default', 'admin', or null
```

The return value is now `?string`: it is `null` before a successful verification
and after a failed verification. Previously it could access an uninitialized
property or retain the previous successful user. The container binding is scoped
so Laravel worker lifecycles clear it between requests/jobs.

## Upgrade notes

- Requires PHP `^8.4` and Laravel components `^13.0`.
- Supports Symfony HTTP Kernel `^7.4 || ^8.0`.
- Fixes PHP 8.4's implicitly nullable parameter deprecation.
- Preserves bcrypt/Argon password-hash verification; no plaintext fallback.
- Loads default configuration during provider registration, including early
  container resolution, while respecting Laravel's cached configuration.
- Supports `vendor:publish --tag=shield-config`; existing provider-based
  publication commands also work.
- This port targets Laravel 13. Legacy Laravel and Lumen are outside its scope.
- `ShieldMiddleware::handle()` now explicitly accepts an Illuminate request.
- `Shield::getCurrentUser()` now returns `?string`; adjust subclass overrides
  and callers that assumed an authenticated user always exists.

## Development

```bash
composer install
composer validate --strict
composer check-platform-reqs
composer test -- --display-all-issues
```

GitHub Actions is configured for PHP 8.4 and 8.5, each with lowest and highest
compatible stable dependencies. See `PORTING.md` for the source baseline and
local verification results.
