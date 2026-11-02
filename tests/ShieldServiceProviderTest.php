<?php

declare(strict_types=1);

namespace Dariushankins\Tests\Shield;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use Dariushankins\Shield\Shield;
use Dariushankins\Shield\ShieldServiceProvider;

class ShieldServiceProviderTest extends TestCase
{
    public function testConfigurationIsAvailableBeforeBoot(): void
    {
        $app = new Application();
        $app->instance('config', new Repository());
        (new ShieldServiceProvider($app))->register();

        $this->assertIsArray($app['config']->get('shield.users'));
        $this->assertInstanceOf(Shield::class, $app->make('shield'));
        $this->assertFalse($app->make(Shield::class)->verify('user', 'password'));
    }

    public function testApplicationConfigurationIsPreserved(): void
    {
        $users = ['custom' => [
            password_hash('user', PASSWORD_BCRYPT, ['cost' => 4]),
            password_hash('password', PASSWORD_BCRYPT, ['cost' => 4]),
        ]];
        $app = new Application();
        $app->instance('config', new Repository(['shield' => ['users' => $users]]));
        (new ShieldServiceProvider($app))->register();

        $this->assertSame($users, $app['config']->get('shield.users'));
        $this->assertTrue($app->make(Shield::class)->verify('user', 'password', 'custom'));
    }

    public function testCachedConfigurationIsUsedWithoutMergingDefaults(): void
    {
        $app = new class extends Application {
            public function configurationIsCached()
            {
                return true;
            }
        };
        $app->instance('config', new Repository(['shield' => ['users' => []]]));
        (new ShieldServiceProvider($app))->register();

        $this->assertSame([], $app['config']->get('shield.users'));
        $this->assertFalse($app->make(Shield::class)->verify('user', 'password'));
    }
}
