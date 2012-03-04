<?php

namespace Alb\OpenIDServerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DefaultController extends Controller
{
    
    public function indexAction($name)
    {
        return $this->render('AlbOpenIDServerBundle:Default:index.html.twig', array('name' => $name));
    }
}
