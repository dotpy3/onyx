<?php

namespace SDF\BilletterieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use SDF\BilletterieBundle\Authentication\Cas\Client\CasClient;
use SDF\BilletterieBundle\Entity\Billet;
use SDF\BilletterieBundle\Entity\Tarif;

class PagesController extends Controller
{
    public function homeAction()
    {
        $config = $this->container->getParameter('sdf_billetterie');
        $authenticationUtils = $this->get('security.authentication_utils');

        $casClient = new CasClient($config['utc_cas']['url']);

        return $this->render('SDFBilletterieBundle:Pages:home.html.twig', array(
            'last_username'           => $authenticationUtils->getLastUsername(),
            'login_error'             => $authenticationUtils->getLastAuthenticationError(),
            'exterior_access_enabled' => $config['settings']['enable_exterior_access'],
            'utc_cas_url'             => $casClient->getLoginUrl($this->generateUrl('sdf_billetterie_cas_callback', array(), true))
        ));
    }

    public function legalsAction()
    {
        return $this->render('SDFBilletterieBundle:Pages:legals.html.twig');
    }
}
