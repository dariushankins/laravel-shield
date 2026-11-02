<?php

/**
 * Copyright (c) Vincent Klaiber
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://github.com/vinkla/laravel-shield
 */

declare(strict_types=1);

namespace Dariushankins\Shield;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class ShieldServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/shield.php' => $this->app->configPath('shield.php'),
            ], 'shield-config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/shield.php', 'shield');

        $this->app->scoped('shield', function (Container $app) {
            $config = $app['config']->get('shield.users', []);

            return new Shield(is_array($config) ? $config : []);
        });

        $this->app->alias('shield', Shield::class);
    }

    public function provides(): array
    {
        return [
            'shield',
            Shield::class,
        ];
    }
}
