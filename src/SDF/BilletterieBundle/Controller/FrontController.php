<?php

namespace SDF\BilletterieBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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
	 * Retrieve a user's ticket
	 *
	 * @param integer The Billet id
	 * @throws NotFoundException A 404 Not Found exception if the ticket does not exists
	 * @return Billet $ticket The Billet instance
	 */
	protected function findTicket($id)
	{
		$ticket = null;
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();

		$ticket = $em->getRepository('SDFBilletterieBundle:Billet')->findOneForUser($id, $user);

		if (!$ticket) {
			// Force a 404 instead of 403 error.
			// User does not have to know if the given ID exists as long as it's not his or her ticket.
			throw $this->createNotFoundException('Impossible de trouver ce ticket...');
		}

		return $ticket;
	}

	/**
	 * Send an HTTP Response with Json encoded content
	 *
	 * @param mixed $data The data to render
	 * @param string $filename The filename to provide
	 * @param integer $dispositionType The HTTP disposition {inline, or attachment}
	 * @param integer $statusCode The HTTP Status-Code
	 * @param array $headers The HTTP headers to join to the response
	 */
	protected function renderDataAsFile($data, $filename = 'File', $dispositionType = ResponseHeaderBag::DISPOSITION_INLINE, $statusCode = Response::HTTP_OK, array $headers = array())
	{
		$response = new Response($data, $statusCode, $headers);

		$disposition = $response->headers->makeDisposition($dispositionType, $filename);
		$response->headers->set('Content-Disposition', $disposition);

		return $response;
	}

	/**
	 * Send an HTTP Response with Json encoded content
	 *
	 * @param array $data The data to encode
	 * @param integer $statusCode The HTTP Status-Code
	 * @param array $headers The HTTP headers to join to the response
	 */
	protected function renderJsonResponse(array $data, $statusCode = JsonResponse::HTTP_OK, array $headers = array())
	{
		return new JsonResponse($data, $statusCode, $headers);
	}
}