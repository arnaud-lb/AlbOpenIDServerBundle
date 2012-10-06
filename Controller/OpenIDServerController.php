<?php

namespace Alb\OpenIDServerBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Acme\OpenidDemoBundle\Storage;
use Alb\OpenIDServerBundle\Adapter\AdapterInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class OpenIDServerController
{
    private $server;
    protected $securityContext;
    protected $formFactory;
    protected $templating;

    public function __construct(Server $server, SecurityContextInterface $securityContext, FormFactoryInterface $formFactory, EngineInterface $templating)
    {
        $this->server = $server;
        $this->securityContext = $securityContext;
        $this->formFactory = $formFactory;
        $this->templating = $templating;
    }

    public function indexAction(Request $request)
    {
        $openidRequest = $this->server->getOpenIdRequest($request);

        if (!$openidRequest) {
            $response = $this->render('AlbOpenIDServerBundle:OpenIDServer:index.html.twig');
            $response->headers->set('X-XRDS-Location', $this->getXrdsUri());
            return $response;
        }

        if (!$this->server->isValidRequest($openidRequest)) {
            return $this->server->createErrorResponse($openidRequest);
        }

        if (in_array($openidRequest->mode, array('checkid_setup', 'checkid_immediate'))) {

            return $this->handleCheckIdRequest($request, $openidRequest);

        } else {

            return $this->server->handleRequest($openidRequest);
        }
    }

    protected function handleCheckIdRequest(Request $request, \Auth_OpenID_CheckIDRequest $openidRequest)
    {
        $this->requireAuthentication();

        $user = $this->securityContext->getToken()->getUser();
        $process = $this->createCheckIdProcess($openidRequest, $user);

        if ($process->isImmediate()) {
            return $process->createNegativeResponse();
        }

        if (!$process->isApproved()) {
            return $this->handleApproval($user, $process);
        }

        return $process->createPositiveResponse();
    }

    protected function handleApproval($user, CheckIdProcess $process)
    {
        $form = $this->createApprovalForm();

        if ('POST' === $request->getMethod()) {

            $form->bindRequest($request);

            if ($form->isValid()) {
                if ($request->request->get('approve')) {
                    return $process->createPositiveResponse();
                } else {
                    return $process->createNegativeResponse();
                }
            }
        }

        $template = 'AlbOpenIDServerBundle:OpenIDServer:approve.html.twig';
        return $this->render($template, array(
            'form' => $form->createView(),
            'requested_fields' => $process->getRequestedFieldsData(),
        ));
    }

    protected function createCheckIdProcess(\Auth_OpenID_CheckIDRequest $openidRequest, $user)
    {
        return new CheckIdProcess($this->server, $openidRequest, $user);
    }

    protected function requireAuthentication()
    {
        if (!$this->securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException;
        }
    }

    protected function createApprovalForm()
    {
        $form = $this->formFactory
            ->createNamedBuilder('form', 'open_id_approval')
            ->getForm();

        return $form;
    }

    public function xrdsAction()
    {
        require_once 'Auth/OpenID/Discover.php';

        $template = 'AlbOpenIDServerBundle:OpenIDServer:xrds.xrds.twig';

        $response = $this->render($template, array(
            'type' => \Auth_OpenID_TYPE_2_0_IDP,
            'uri' => $this->server->getEndpointUri(),
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
            'X-XRDS-Location' => $this->server->getIdentifierUriFromUnique($unique, array(
                '_format' => 'xrds',
            )),
        ));
    }

    protected function handleServerError($error)
    {
        if ($error instanceof \Auth_OpenID_ServerError) {
            throw new HttpException(400, $error->text);
        } else {
            throw new HttpException(400, 'Uknown error');
        }
    }

    protected function render($view, array $parameters = array(), Response $response = null)
    {
        return $this->templating->renderResponse($view, $parameters, $response);
    }
}

