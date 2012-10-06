<?php

namespace Alb\OpenIDServerBundle\Server;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Alb\OpenIDServerBundle\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Response;

class Server
{
    private $router;
    private $adapter;
    private $server;

    public function __construct(RouterInterface $router, AdapterInterface $adapter, \Auth_OpenID_Server $server)
    {
        $this->router = $router;
        $this->adapter = $adapter;
        $this->server = $server;
    }

    public function createResponse($openidResponse)
    {
        if ($openidResponse instanceof \Auth_OpenID_EncodingError) {
            throw new HttpException(400, $openidWebResponse->response->text);
        }

        $openidWebResponse = $this->server->encodeResponse($openidResponse);

        return new Response(
            $openidWebResponse->body,
            $openidWebResponse->code,
            (array) $openidWebResponse->headers
        );
    }

    public function getOpenIdRequest(Request $request)
    {
        $server = $this->getServer();

        if ('POST' === $request->getMethod()) {
            $params = $request->getContent();
            $params = $this->decodeQuery($params);
        } else {
            $params = $request->server->get('QUERY_STRING');
            $params = $this->decodeQuery($params);
        }

        $openidRequest = $server->decodeRequest($params);

        return $openidRequest;
    }

    public function isValidRequest($openidRequest)
    {
        return $openidRequest instanceof \Auth_OpenID_Request;
    }

    public function handleRequest($openidRequest)
    {
        $server = $this->getServer();

        $openidResponse = $server->handleRequest($openidRequest);

        return $this->createResponse($openidResponse);
    }

    public function getIdentifierUri($user, array $params = array())
    {
        $unique = $this->adapter->getUserUnique($user);

        return $this->getIdentifierUriFromUnique($unique, $params);
    }

    public function getIdentifierUriFromUnique($unique, array $params = array())
    {
        return $this->generateUrl('alb_open_id_server_identifier', array(
            'unique' => $unique,
        ) + $params, true);
    }

    public function getEndpointUri()
    {
        return $this->generateUrl('alb_open_id_server_endpoint', array(), true);
    }

    public function getXrdsUri()
    {
        return $this->generateUrl('alb_open_id_server_xrds', array(), true);
    }

    protected function generateUrl($route, $parameters = array(), $absolute = false)
    {
        return $this->router->generate($route, $parameters, $absolute);
    }

    protected function getServer()
    {
        $server = $this->server;
        $server->op_endpoint = $this->getEndpointUri();

        return $server;
    }

    protected function decodeQuery($query)
    {
        // PHP replaces '.' with '_' when parsing query strings
        // so we have to do it ourselves
        return \Auth_OpenID::params_from_string($query);
    }
}

