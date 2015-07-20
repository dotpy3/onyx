<?php

namespace SDF\BilletterieBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontController extends Controller
{
	private function instantLog($user,$content)
	{
		$em = $this->getDoctrine()->getManager();

		$log = new Log();
		$log->setInstantLogAs($user,$content);

		$em->persist($log);
		$em->flush();
	}

	protected function countRemainingPlacesForPrice($price)
	{
		$remainingPlaces = 0;
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();

		// Check the event global capacity
		$numberOfPlacesSold = $em->getRepository('SDFBilletterieBundle:Billet')->countAllSoldForEvent($price->getEvenement());
		$eventTotalCapacity = $price->getEvenement()->getQuantiteMax();

		$remainingPlacesForEvent = $eventTotalCapacity - $numberOfPlacesSold;

		if ($remainingPlacesForEvent > 0) {
			// Check the price capacity
			$numberOfPlacesSoldForPrice = $em->getRepository('SDFBilletterieBundle:Billet')->countAllSoldForPrice($price);
			$priceTotalCapacity = $price->getQuantite();

			$remainingPlacesForPrice = $priceTotalCapacity - $numberOfPlacesSoldForPrice;

			if ($remainingPlacesForPrice > 0) {
				// Check the places allowed by price for the user
				$numberOfPlacesBoughtByUser = $em->getRepository('SDFBilletterieBundle:Billet')->countAllSoldForPriceAndUser($price, $user);
				$maxPlacesAllowedForUser = $price->getQuantiteParPersonne();

				$remainingPlacesForUser = $maxPlacesAllowedForUser - $numberOfPlacesBoughtByUser;

				if ($remainingPlacesForUser > 0) {
					$remainingPlaces = min($remainingPlacesForEvent, $remainingPlacesForPrice, $remainingPlacesForUser);
				}
			}
		}

		return $remainingPlaces;
	}
}