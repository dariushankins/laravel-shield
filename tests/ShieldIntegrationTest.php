<?php

declare(strict_types=1);

namespace Dariushankins\Tests\Shield;

use Dariushankins\Shield\Shield;
use Dariushankins\Shield\ShieldMiddleware;
use Dariushankins\Shield\ShieldServiceProvider;

class ShieldIntegrationTest extends AbstractTestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('shield.users', $this->getUsers());
    }

    protected function defineRoutes($router): void
    {
        $router->aliasMiddleware('shield', ShieldMiddleware::class);
        $router->get('/protected', fn() => response()->json([
            'user' => app(Shield::class)->getCurrentUser(),
        ]))->middleware('shield');
        $router->get('/named', fn() => 'allowed')->middleware('shield:hasselhoff');
    }

    public function testMiddlewareProtectsLaravelRoutes(): void
    {
        $this->get('/protected')->assertUnauthorized()->assertHeader('WWW-Authenticate', 'Basic');
        $this->withBasicAuth('user1', 'password1')->get('/protected')
            ->assertOk()->assertExactJson(['user' => 'default']);
        $this->withBasicAuth('user1', 'wrong')->get('/protected')->assertUnauthorized();
        $this->assertNull($this->app->make(Shield::class)->getCurrentUser());
    }

    public function testNamedUserRestrictionWorksThroughTheRouter(): void
    {
        $this->withBasicAuth('user1', 'password1')->get('/named')->assertUnauthorized();
        $this->withBasicAuth('user2', 'password2')->get('/named')->assertOk()->assertSee('allowed');
    }

    public function testAuthorizationHeaderIsReadAsBasicAuth(): void
    {
        $this->get('/protected', ['Authorization' => 'Basic ' . base64_encode('user1:password1')])
            ->assertOk()->assertJsonPath('user', 'default');
    }

    public function testUnconfiguredCredentialsReturnUnauthorized(): void
    {
        $this->app['config']->set('shield.users', ['default' => [null, null]]);
        $this->withBasicAuth('user1', 'password1')->get('/protected')->assertUnauthorized();
    }

    public function testMalformedUsersConfigurationReturnsUnauthorized(): void
    {
        $this->app['config']->set('shield.users', 'invalid');
        $this->withBasicAuth('user1', 'password1')->get('/protected')->assertUnauthorized();
    }

    public function testContainerAliasUsesTheSameInstanceWithinAScope(): void
    {
        $this->assertSame($this->app->make('shield'), $this->app->make(Shield::class));
    }

    public function testWorkerScopeResetClearsAuthentication(): void
    {
        $shield = $this->app->make(Shield::class);
        $this->assertTrue($shield->verify('user1', 'password1'));
        $this->app->forgetScopedInstances();
        $next = $this->app->make(Shield::class);
        $this->assertNotSame($shield, $next);
        $this->assertNull($next->getCurrentUser());
    }

    public function testConfigCanBePublishedUsingTheDocumentedTag(): void
    {
        $paths = ShieldServiceProvider::pathsToPublish(ShieldServiceProvider::class, 'shield-config');
        $source = __DIR__ . '/../config/shield.php';
        $this->assertCount(1, $paths);
        $this->assertSame($this->app->configPath('shield.php'), array_values($paths)[0]);
        $this->assertSame(realpath($source), realpath(array_keys($paths)[0]));
    }
}
