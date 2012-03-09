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

    /**
     * Returns an array of field name => field value
     *
     * The function should return any field that's requested, but is not
     * required to return all fields.
     *
     * The fields will be sent to the consumer if the user accepts to.
     *
     * @param  mixed $user A user instance
     * @param  array $requestedFields Field requested by the consumer
     * @return array An array of field name => field value
     */
    function getUserData($user, array $requestedFields);
}
