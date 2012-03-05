<?php

namespace Alb\OpenIDServerBundle\Adapter;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Default adapter that should work with Symfony\Component\Security\Core\User\UserInterface users
 */
class DefaultAdapter implements AdapterInterface
{
    protected function checkUser($user)
    {
        if (!$user instanceof UserInterface) {
            $type = is_object($user) ? get_class($user) : gettype($user);
            throw new \InvalidArgumentException(sprintf('Expected a Symfony\Component\Security\Core\User\UserInterface, got a %s', $type));
        }
    }

    public function getUserUnique($user)
    {
        $this->checkUser($user);
        return $user->getUsername();
    }
}
