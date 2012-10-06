<?php

namespace Alb\OpenIDServerBundle\Tests\Server;

use Alb\OpenIDServerBundle\Server\Server;
use Symfony\Component\Routing\RouterInterface;
use Alb\OpenIDServerBundle\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Request;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    private $server;
    private $openidServer;
    private $router;
    private $adapter;

    public function prepare($mockMethods = array())
    {
        $this->openidServer = $this->getMock('Auth_OpenID_Server', $mockMethods, array(new \Auth_OpenID_DumbStore('x'), 'http://www.example.org'));
        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $this->adapter = $this->getMock('Alb\OpenIDServerBundle\Adapter\AdapterInterface');

        $this->server = new Server($this->router, $this->adapter, $this->openidServer);
    }

    public function testCreateResponseReturnsResponse()
    {
        $this->prepare(array('encodeResponse'));

        $openidRequest = $this->openidServer->decodeRequest(array(
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.mode' => 'checkid_setup',
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.realm' => 'http://www.example.com/',
            'openid.return_to' => 'http://www.example.com/',
        ));

        $openidResponse = $openidRequest->answer(true);

        $this->openidServer->expects($this->once())
            ->method('encodeResponse')
            ->with($openidResponse)
            ->will($this->returnValue(new \Auth_OpenID_WebResponse(201, array('X-Foo' => 'bar'), 'body')))
            ;

        $response = $this->server->createResponse($openidResponse);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('bar', $response->headers->get('X-Foo'));
        $this->assertSame('body', $response->getContent());
    }

    public function testGetOpenIdRequestWithPostRequestUsesPostData()
    {
        $this->prepare(array('decodeRequest'));

        $request = Request::create('/', 'POST', array(), array(), array(), array(), 'foo=bar');

        $this->openidServer->expects($this->once())
            ->method('decodeRequest')
            ->with(array('foo' => 'bar'))
            ->will($this->returnValue('decoded'))
            ;

        $this->assertSame('decoded', $this->server->getOpenIdRequest($request));
    }

    public function testGetOpenIdRequestWithGetRequestUsesQueryString()
    {
        $this->prepare(array('decodeRequest'));

        $request = Request::create('/?foo=bar');

        $this->openidServer->expects($this->once())
            ->method('decodeRequest')
            ->with(array('foo' => 'bar'))
            ->will($this->returnValue('decoded'))
            ;

        $this->assertSame('decoded', $this->server->getOpenIdRequest($request));
    }

    /**
     * @dataProvider getIsValidRequestData
     */
    public function testIsValidRequest($request, $expect)
    {
        $this->prepare();

        $this->assertSame($expect, $this->server->isValidRequest($request));
    }

    public function getIsValidRequestData()
    {
        require_once 'Auth/OpenID/Server.php';

        return array(
            array(new \Auth_OpenID_Request, true),
            array(new \stdClass, false),
            array(null, false),
        );
    }

    public function testHandleRequestReturnsResponse()
    {
        $this->prepare(array('handleRequest', 'encodeResponse'));

        $request = new \Auth_OpenID_CheckIDRequest('http://www.example.com', 'http://www.example.com');
        $response = new \Auth_OpenID_ServerResponse($request);
        $webResponse = new \Auth_OpenID_WebResponse(200, array(), 'test');

        $this->openidServer->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->will($this->returnValue($response))
            ;

        $this->openidServer->expects($this->once())
            ->method('encodeResponse')
            ->with($response)
            ->will($this->returnValue($webResponse))
            ;

        $response = $this->server->handleRequest($request);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame('test', $response->getContent());
    }

    
}

