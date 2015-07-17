<?php

namespace SDF\BilletterieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use SDF\BilletterieBundle\Entity\Billet;
use SDF\BilletterieBundle\Entity\Tarif;



class DefaultController extends Controller
{

    $exterieurAccess = true;

    public function indexAction()
    {
    	return $this->render('SDFBilletterieBundle:Default:index.html.twig', array('connexionError' => false, 'inscriptionReussie' => false, 'accesExterieur' => $this->exterieurAccess));
    }

    public function errorIndexAction()
    {
    	return $this->render('SDFBilletterieBundle:Default:index.html.twig', array('connexionError' => true, 'inscriptionReussie' => false, 'accesExterieur' => $this->exterieurAccess));
    }

    public function getCGVAction(){
    	return $this->render('SDFBilletterieBundle:Default:cgv.html.twig');
    }

    public function adminStatsAction(){
        $em= $this->getDoctrine()->getManager();
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

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
