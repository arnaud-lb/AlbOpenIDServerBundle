<?php

namespace Alb\OpenIDServerBundle\Server;

use Alb\OpenIDServerBundle\Adapter\AdapterInterface;

class CheckIdProcess
{
    protected $server;
    protected $openidRequest;
    protected $user;
    protected $sRegRequest;

    public function __construct(Server $server, \Auth_OpenID_CheckIDRequest $request, $user)
    {
        $this->server = $server;
        $this->openidRequest = $request;
        $this->user = $user;
    }

    public function isApproved()
    {
        return false;
    }

    public function isImmediate()
    {
        return $request->immediate;
    }

    public function createPositiveResponse()
    {
        $openidResponse = $this->openidRequest->answer(
            true,
            null,
            $this->getLocalId(),
            $this->getClamedId()
        );

        $sRegRequest = $this->getSRegRequest();
        $fields = $this->getRequestedFieldsData();

        $sRegResponse = \Auth_OpenID_SRegResponse::extractResponse($sRegRequest, $fields);
        $sRegResponse->toMessage($openidResponse->fields);

        return $this->server->createResponse($openidResponse);
    }

    public function createNegativeResponse()
    {
        $openidResponse = $this->openidRequest->answer(false);
        return $this->server->createResponse($openidResponse);
    }

    protected function getSRegRequest()
    {
        if (null !== $sRegRequest = $this->sRegRequest) {
            return $sRegRequest;
        }

        require_once 'Auth/OpenID/SReg.php';

        return $this->sRegRequest = \Auth_OpenID_SRegRequest::fromOpenIDRequest($this->openidRequest);
    }

    public function getRequestedFieldsData()
    {
        $fields = $this->getFieldsData($this->user, $this->getSRegRequest());
        return $fields;
    }

    protected function getFieldsData($user, $sRegRequest)
    {
        $fields = array_merge(
            $sRegRequest->optional,
            $sRegRequest->required
        );

        return $this->adapter->getUserData($user, $fields);
    }

    protected function getClaimedId()
    {
        $unique = $this->server->getIdentifierUrl($this->user);
    }

    protected function getLocalId()
    {
        return $this->getClaimedId();
    }
}

