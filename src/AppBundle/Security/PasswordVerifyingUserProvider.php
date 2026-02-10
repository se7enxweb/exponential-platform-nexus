<?php

namespace AppBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use eZ\Publish\Core\MVC\Symfony\Security\User;

class PasswordVerifyingUserProvider implements UserProviderInterface
{
    private $innerProvider;

    public function __construct(UserProviderInterface $innerProvider)
    {
        $this->innerProvider = $innerProvider;
    }

    public function loadUserByUsername($username)
    {
        return $this->innerProvider->loadUserByUsername($username);
    }

    public function refreshUser(\Symfony\Component\Security\Core\User\UserInterface $user)
    {
        return $this->innerProvider->refreshUser($user);
    }

    public function supportsClass($class)
    {
        return $this->innerProvider->supportsClass($class);
    }
}
