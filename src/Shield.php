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

class Shield
{
    protected ?string $currentUser = null;

    public function __construct(
        protected array $users,
    ) {
        //
    }

    public function verify(?string $username, ?string $password, ?string $user = null): bool
    {
        $this->currentUser = null;

        if ($username === null || $password === null) {
            return false;
        }

        $users = $this->getUsers($user);

        foreach ($users as $user => $credentials) {
            if (!is_array($credentials) || count($credentials) !== 2) {
                continue;
            }

            [$usernameHash, $passwordHash] = array_values($credentials);

            if (!is_string($usernameHash) || !is_string($passwordHash)) {
                continue;
            }

            if (
                password_verify($username, $usernameHash)
                && password_verify($password, $passwordHash)
            ) {
                $this->currentUser = (string) $user;

                return true;
            }
        }

        return false;
    }

    protected function getUsers(?string $user = null): array
    {
        if ($user !== null) {
            return array_intersect_key($this->users, array_flip((array) $user));
        }

        return $this->users;
    }

    public function getCurrentUser(): ?string
    {
        return $this->currentUser;
    }
}
