<?php

namespace Alb\OpenIDServerBundle\Adapter;

use Symfony\Component\Security\Core\User\UserInterface;
use FOS\UserBundle\Model\UserInterface as FOSUserInterface;

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

    public function getUserData($user, array $requestedFields)
    {
        $this->checkUser($user);

        $data = array();

        if (in_array('nickname', $requestedFields)) {
            $data['nickname'] = $user->getUsername();
        }

        if ($user instanceof FOSUserInterface) {
            if (in_array('email', $requestedFields)) {
                $data['email'] = $user->getEmail();
            }
        }

        return $data;
    }
}

