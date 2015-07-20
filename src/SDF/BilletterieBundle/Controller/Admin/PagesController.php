<?php

namespace SDF\BilletterieBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use SDF\BilletterieBundle\Entity\Billet;
use SDF\BilletterieBundle\Entity\Tarif;

class PagesController extends Controller
{
    public function homeAction()
    {
        return $this->render('SDFBilletterieBundle:Pages/Admin:home.html.twig');
    }

    public function statsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repoBillets = $em->getRepository('SDFBilletterieBundle:Billet');

        $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b');
        $resultat = $query->getResult();
        $output['nbBilletsGeneres'] = $resultat[0]['c'];

        $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b WHERE b.valide = TRUE');
        $resultat = $query->getResult();
        $output['nbBilletsPayes'] = $resultat[0]['c'];

        $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t WHERE t.id = 14 AND b.valide = TRUE');
        $resultat = $query->getResult();
        $output['nbBilletsVague1'] = $resultat[0]['c'];

        $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t WHERE t.id = 15 AND b.valide = TRUE');
        $resultat = $query->getResult();
        $output['nbBilletsVague2'] = $resultat[0]['c'];

        $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t WHERE t.id = 16 AND b.valide = TRUE');
        $resultat = $query->getResult();
        $output['nbBilletsVague3'] = $resultat[0]['c'];

        $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t WHERE t.id = 17 AND b.valide = TRUE');
        $resultat = $query->getResult();
        $output['nbBilletsVague4'] = $resultat[0]['c'];

        $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t WHERE t.id = 19 AND b.valide = TRUE');
        $resultat = $query->getResult();
        $output['nbBilletsNonCotisant'] = $resultat[0]['c'];

        $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t WHERE t.id = 20 AND b.valide = TRUE');
        $resultat = $query->getResult();
        $output['nbBilletsExterieur'] = $resultat[0]['c'];

        return new JsonResponse($output);
    }
}