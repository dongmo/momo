<?php

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 24/09/2016
 * Time: 20:43
 *
 * Class LanguageController to switch between language: for the moment just Fr and En.
 * @package AppBundle\Controller\Language
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class LanguageController extends Controller
{
    /**
     * Set language to English.
     * @Route("/language/en", name="language_en")
     */
    public function englishAction(Request $request)
    {
        $this->get('session')->set('_locale', 'en');
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Set language to French.
     * @Route("/language/fr", name="language_fr")
     */
    public function frenchAction(Request $request)
    {
        $this->get('session')->set('_locale', 'fr');
        return $this->redirect($request->headers->get('referer'));
    }
}
