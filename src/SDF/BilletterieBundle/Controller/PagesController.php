<?php

namespace SDF\BilletterieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use SDF\BilletterieBundle\Authentication\Cas\Client\CasClient;
use SDF\BilletterieBundle\Entity\Billet;
use SDF\BilletterieBundle\Entity\Tarif;

class PagesController extends FrontController
{
	public function homeAction()
	{
		$config = $this->container->getParameter('sdf_billetterie');
		$authenticationUtils = $this->get('security.authentication_utils');

		$casClient = new CasClient($config['utc_cas']['url']);

		$user = $this->getUser();

		$boughtTickets = array();
		$unvalidTicket = null;
		$availablePrices = array();
		$remainingPlacesByPrice = array();

		if ($user) {
			$em = $this->getDoctrine()->getManager();

			// Find all the tickets bought by the user (and validated by PayUtc)
			$boughtTickets = $em->getRepository('SDFBilletterieBundle:Billet')->findAllValidTicketsForUser($user);

			// Find all the available prices for the user [Without stock availability management]
			// Then handle stocks availability
			$availablePrices = $em->getRepository('SDFBilletterieBundle:Tarif')->findAllAvailablePricesForUser($user);

			$availablePrices = array_filter($availablePrices, function ($price) use ($user, $em, &$remainingPlacesByPrice) {
				// Assume that the price is available by default
				$isPriceAvailable = false;

				$remainingPlaces = $this->countRemainingPlacesForPrice($price);

				if ($remainingPlaces > 0) {
					$isPriceAvailable = true;
					$remainingPlacesByPrice[$price->getId()] = $remainingPlaces;
				}

				return $isPriceAvailable;
			});

			/* ON RECUPERE LES BILLETS NON VALIDÉS */
			$unvalidTicket = $em->getRepository('SDFBilletterieBundle:Billet')->findOneUnvalidTicketsForUser($user);
		}

		return $this->render('SDFBilletterieBundle:Pages:home.html.twig', array(
			'last_username'           => $authenticationUtils->getLastUsername(),
			'login_error'             => $authenticationUtils->getLastAuthenticationError(),
			'exterior_access_enabled' => $config['settings']['enable_exterior_access'],
			'utc_cas_url'             => $casClient->getLoginUrl($this->generateUrl('sdf_billetterie_cas_callback', array(), true)),
			'boughtTickets'           => $boughtTickets,
			'unvalidTicket'           => $unvalidTicket,
			'availablePrices'         => $availablePrices,
			'remainingPlacesByPrice'  => $remainingPlacesByPrice,
		));
	}

	public function legalsAction()
	{
		return $this->render('SDFBilletterieBundle:Pages:legals.html.twig');
	}
}
