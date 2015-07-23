<?php

namespace SDF\BilletterieBundle\Controller\Pages\Admin;

use Exception;
use \Payutc\Client\JsonException;

use SDF\BilletterieBundle\Controller\FrontController;

class PagesController extends FrontController
{
	public function homeAction()
	{
		return $this->render('SDFBilletterieBundle:Pages/Admin:home.html.twig');
	}

	public function checkPayutcStatusAction()
	{
		$payutcClient = $this->get('payutc_client');
		$content = array();

		try {
			$status = $payutcClient->getStatus(true);

			$content['status'] = (array) $status->application;
		}
		catch (JsonException $e) {
			$content['error'] = true;
			$content['message'] = $e->getMessage();
			$content['exception'] = $e->getMessage();
		}

		return $this->renderJsonResponse($content);
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

		return $this->renderJsonResponse($output);
	}
}