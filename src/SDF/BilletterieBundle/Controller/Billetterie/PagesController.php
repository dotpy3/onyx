<?php

namespace SDF\BilletterieBundle\Controller\Billetterie;

use SDF\BilletterieBundle\Controller\FrontController;
use SDF\BilletterieBundle\Authentication\Cas\Client\CasClient;

class PagesController extends FrontController
{
	public function homeAction()
	{
		$authenticationUtils = $this->get('security.authentication_utils');
		$casClient = $this->get('cas_client');
		$user = $this->getUser();

		$boughtTickets = array();
		$unvalidTicket = null;
		$availablePrices = array();
		$remainingPlacesByPrice = array();

		if ($user) {
			$em = $this->getDoctrine()->getManager();

			// Find all tickets bought by the user (and validated by PayUtc)
			$boughtTickets = $em->getRepository('SDFBilletterieBundle:Billet')->findAllValidTicketsForUser($user);

			// Find all available prices for the user [Without stock availability management]
			$availablePrices = $em->getRepository('SDFBilletterieBundle:Tarif')->findAllAvailablePricesForUser($user);
			// Then handle stocks availability
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

			// Find an optional ticket ordered by the user but not yet validated by PayUtc
			$unvalidTicket = $em->getRepository('SDFBilletterieBundle:Billet')->findOneUnvalidTicketsForUser($user);
		}

		return $this->render('SDFBilletterieBundle:Pages:home.html.twig', array(
			'last_username'           => $authenticationUtils->getLastUsername(),
			'login_error'             => $authenticationUtils->getLastAuthenticationError(),
			'exterior_access_enabled' => $this->container->getParameter('sdf_billetterie.settings.enable_exterior_access'),
			'utc_cas_url'             => $casClient->getAbsoluteLoginUrl($this->generateUrl('sdf_billetterie_cas_callback', array(), true)),
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
