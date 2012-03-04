<?php

namespace Alb\OpenIDServerBundle\Adapter;

interface AdapterInterface
{
    /**
     * Returns a unique string identifying the user (e.g. the user id)
     *
     * @param mixed $user User returned by Token::getUser()
     */
    function getUserUnique($user);
}
