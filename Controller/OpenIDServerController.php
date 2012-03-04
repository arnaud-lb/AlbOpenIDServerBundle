<?php

namespace Alb\OpenIDServerBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Acme\OpenidDemoBundle\Storage;
use Alb\OpenIDServerBundle\Adapter\AdapterInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class OpenIDServerController
{
    private $server;
    protected $adapter;
    protected $securityContext;
    protected $router;
    protected $formFactory;
    protected $templating;

    public function __construct(\Auth_OpenID_Server $server, AdapterInterface $adapter, SecurityContextInterface $securityContext, RouterInterface $router, FormFactoryInterface $formFactory, EngineInterface $templating)
    {
        $this->server = $server;
        $this->adapter = $adapter;
        $this->securityContext = $securityContext;
        $this->router = $router;
        $this->formFactory = $formFactory;
        $this->templating = $templating;
    }

    public function indexAction(Request $request)
    {
        $server = $this->getServer();

        // PHP replaces '.' with '_' when parsing query strings
        // so we have to do it ourselves

        if ('POST' === $request->getMethod()) {
            $params = $request->getContent();
            $params = $this->decodeQuery($params);
        } else {
            $params = $request->server->get('QUERY_STRING');
            $params = $this->decodeQuery($params);
        }

        $openidRequest = $server->decodeRequest($params);

        if (!$openidRequest) {
            return new Response('', 200, array(
                'X-XRDS-Location' => $this->getXrdsUri(),
            ));
        }

        if ('checkid_setup' === $openidRequest->mode) {

            $uri = $this->getTrustUri($params);
            return new RedirectResponse($uri);

        } else if ('checkid_immediate' === $openidRequest->mode) {

            $openidResponse = $openidRequest->answer(true, null, $identifier);
            $webResponse = $server->encodeResponse($openidResponse);
            return $this->convertResponse($webResponse);

        } else {

            $openidResponse = $server->handleRequest($openidRequest);
            $webResponse = $server->encodeResponse($openidResponse);

            return $this->convertResponse($webResponse);
        }
    }

    public function trustAction(Request $request)
    {
        $form = $this->formFactory
            ->createNamedBuilder('form', 'openid_trust')
            ->getForm()
            ;

        $server = $this->getServer();

        $params = $request->server->get('QUERY_STRING');
        $params = $this->decodeQuery($params);

        $openidRequest = $server->decodeRequest($params);

        if (!$openidRequest) {
            throw new HttpException(400);
        }

        if ('checkid_setup' !== $openidRequest->mode) {
            throw new HttpException(400);
        }

        if ('POST' === $request->getMethod()) {

            $form->bindRequest($request);

            $trust = $request->request->get('trust');

            if ($form->isValid() && !empty($trust)) {
                
                $unique = $this->adapter->getUserUnique($this->securityContext->getToken()->getUser());

                $identifier = $this->getIdentifierUri($unique);

                $openidResponse = $openidRequest->answer(true, null, $identifier);
                $webResponse = $server->encodeResponse($openidResponse);
                return $this->convertResponse($webResponse);
            }
        }

        $template = 'AlbOpenIDServerBundle:OpenIDServer:trust.html.twig';
        return $this->render($template, array(
            'form' => $form->createView(),
            'form_action' => $this->getTrustUri($params),
        ));
    }

    public function xrdsAction()
    {
        require_once 'Auth/OpenID/Discover.php';

        $template = 'AlbOpenIDServerBundle:OpenIDServer:xrds.xrds.twig';

        $response = $this->render($template, array(
            'type' => \Auth_OpenID_TYPE_2_0_IDP,
            'uri' => $this->getEndpointUri(),
        ));

        $response->headers->set('Content-Type', 'application/xrds+xml');

        return $response;
    }

    public function identifierAction($unique, $_format)
    {
        if ('xrds' === $_format) {
            $template = 'AlbOpenIDServerBundle:OpenIDServer:identifier.xrds.twig';
            $response = $this->render($template, array(
                'types' => array(
                    \Auth_OpenID_TYPE_2_0,
                    \Auth_OpenID_TYPE_1_1,
                ),
                'uri' => $this->getEndpointUri(),
            ));

            $response->headers->set('Content-Type', 'application/xrds+xml');

            return $response;
        }

        return new Response('', 200, array(
            'X-XRDS-Location' => $this->getIdentifierUri($unique, array(
                '_format' => 'xrds',
            )),
        ));
    }

    protected function convertResponse($openidWebResponse)
    {
        if ($openidWebResponse instanceof \Auth_OpenID_EncodingError) {
            throw new HttpException(400, $openidWebResponse->response->text);
        }

        return new Response(
            $openidWebResponse->body,
            $openidWebResponse->code,
            (array) $openidWebResponse->headers
        );
    }

    protected function decodeQuery($query)
    {
        return \Auth_OpenID::params_from_string($query);
    }

    protected function getServer()
    {
        $server = $this->server;
        $server->op_endpoint = $this->getEndpointUri();

        return $server;
    }

    protected function getEndpointUri()
    {
        return $this->generateUrl('alb_open_id_server_endpoint', array(), true);
    }

    protected function getXrdsUri()
    {
        return $this->generateUrl('alb_open_id_server_xrds', array(), true);
    }

    protected function getTrustUri($params)
    {
        return $this->generateUrl('alb_open_id_server_trust', $params);
    }

    protected function getIdentifierUri($unique, $params = array())
    {
        return $this->generateUrl('alb_open_id_server_identifier', array(
            'unique' => $unique,
        ) + $params, true);
    }

    protected function generateUrl($route, $parameters = array(), $absolute = false)
    {
        return $this->router->generate($route, $parameters, $absolute);
    }

    protected function render($view, array $parameters = array(), Response $response = null)
    {
        return $this->templating->renderResponse($view, $parameters, $response);
    }
}
