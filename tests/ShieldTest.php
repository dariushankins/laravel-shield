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

namespace Dariushankins\Tests\Shield;

use ReflectionClass;
use Dariushankins\Shield\Shield;

class ShieldTest extends AbstractTestCase
{
    public function testVerify()
    {
        $shield = $this->getShield();
        $this->assertTrue($shield->verify('user1', 'password1'));
        $this->assertTrue($shield->verify('user2', 'password2'));
    }

    public function testVerifyWithUser()
    {
        $shield = $this->getShield();
        $this->assertTrue($shield->verify('user2', 'password2', 'hasselhoff'));
    }

    public function testGetCurrentUser()
    {
        $shield = $this->getShield();

        $this->assertTrue($shield->verify('user1', 'password1'));
        $this->assertSame('default', $shield->getCurrentUser());

        $this->assertTrue($shield->verify('user2', 'password2'));
        $this->assertSame('hasselhoff', $shield->getCurrentUser());
    }

    public function testUnauthorizedUser()
    {
        $shield = $this->getShield();
        $this->assertFalse($shield->verify('user3', 'password3'));
    }

    public function testUnauthorizedUserWithNullableCredentials()
    {
        $shield = $this->getShield();
        $this->assertFalse($shield->verify(null, null));
    }

    public function testUnauthorizedWithUser()
    {
        $shield = $this->getShield();
        $this->assertFalse($shield->verify('user1', 'password1', 'hasselhoff'));
    }

    public function testGetUsers()
    {
        $rc = new ReflectionClass(Shield::class);
        $method = $rc->getMethod('getUsers');

        $shield = new Shield([1, 2]);
        $return = $method->invokeArgs($shield, []);
        $this->assertSame(2, count($return));

        $shield = new Shield([1, 2]);
        $return = $method->invokeArgs($shield, ['1']);
        $this->assertSame(1, count($return));
    }

    public function testCurrentUserIsNullBeforeAuthentication(): void
    {
        $this->assertNull($this->getShield()->getCurrentUser());
    }

    public function testFailedAuthenticationClearsTheCurrentUser(): void
    {
        $shield = $this->getShield();
        $this->assertTrue($shield->verify('user1', 'password1'));
        $this->assertFalse($shield->verify('user1', 'wrong-password'));
        $this->assertNull($shield->getCurrentUser());
        $this->assertTrue($shield->verify('user2', 'password2'));
        $this->assertFalse($shield->verify(null, null));
        $this->assertNull($shield->getCurrentUser());
    }

    public function testCredentialsCannotBeCombinedAcrossUsers(): void
    {
        $shield = $this->getShield();
        $this->assertFalse($shield->verify('user1', 'password2'));
        $this->assertFalse($shield->verify('user2', 'password1'));
        $this->assertFalse($shield->verify('user1', null));
        $this->assertFalse($shield->verify(null, 'password1'));
        $this->assertFalse($shield->verify('user1', 'password1', 'missing'));
    }

    public function testInvalidConfigurationFailsClosed(): void
    {
        foreach ([[], ['default' => null], ['default' => 'invalid'],
            ['default' => []], ['default' => ['only-one-hash']],
            ['default' => [null, null]], ['default' => [123, false]],
            ['default' => ['invalid-hash', 'invalid-hash']]] as $users) {
            $shield = new Shield($users);
            $this->assertFalse($shield->verify('user1', 'password1'));
            $this->assertNull($shield->getCurrentUser());
        }
    }

    public function testInvalidUserDoesNotPreventAnotherUserFromAuthenticating(): void
    {
        $shield = new Shield(['invalid' => [null, null]] + $this->getUsers());
        $this->assertTrue($shield->verify('user2', 'password2'));
        $this->assertSame('hasselhoff', $shield->getCurrentUser());
    }

    public function testNumericUserKeysRemainSupported(): void
    {
        $users = $this->getUsers();
        $shield = new Shield([7 => $users['default']]);
        $this->assertTrue($shield->verify('user1', 'password1', '7'));
        $this->assertSame('7', $shield->getCurrentUser());
    }

    protected function getShield()
    {
        return new Shield($this->getUsers());
    }
}
