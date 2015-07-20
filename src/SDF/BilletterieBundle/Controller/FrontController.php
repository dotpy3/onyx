<?php

namespace SDF\BilletterieBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use SDF\BilletterieBundle\Entity\User;
use SDF\BilletterieBundle\Entity\Tarif;
use SDF\BilletterieBundle\Entity\Log;

/**
 * Main Front-End controller for the BilletterieBundle
 * Provides methods shared by controllers
 *
 * @author Florent Schildknecht <florent.schildknecht@gmail.com>
 */
class FrontController extends Controller
{
	/**
	 * Count the remaining places for a given Tarif
	 *
	 * @param User $user The associated User
	 * @param string $content The message
	 */
	protected function instantLog(User $user, $content = '')
	{
		$em = $this->getDoctrine()->getManager();

		$log = new Log();
		$log->setInstantLogAs($user, $content);

		$em->persist($log);
		$em->flush();
	}

	/**
	 * Count the remaining places for a given Tarif
	 *
	 * @param Tarif $price The Tarif entity
	 * @return integer
	 */
	protected function countRemainingPlacesForPrice(Tarif $price)
	{
		$remainingPlaces = 0;

		if ($price) {
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
		}

		return $remainingPlaces;
	}

	/**
	 * Send an HTTP Response with Json encoded content
	 *
	 * @param array $data The data to encode
	 * @param integer $statusCode The HTTP Status-Code
	 * @param array $headers The HTTP headers to join to the response
	 */
	private function renderJsonResponse(array $data, $statusCode = 200, array $headers = array())
	{
		return new JsonResponse($data, $statusCode, $headers);
	}
}