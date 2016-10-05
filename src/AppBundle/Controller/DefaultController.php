<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        ]);
    }

    /**
     * @Route("/contact", name="contactpage")
     */
    public function contactAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/contact.html.twig');
    }

    /**
     * @Route("/api", name="api")
     */
    public function apiAction(Request $request)
    {
    }


    public function requestPaymentCompletedAction(Request $request)
    {


        // Obtenir les données encodées en Xml soumises avec une action PUT/POST


        $data = $request->request->all();
        var_dump($data);
        $arraydecode = json_encode($data);
        $response = new Response($arraydecode);
        //$response->headers->set('Content-Type', 'application/json');

        return $response;

    }

}
