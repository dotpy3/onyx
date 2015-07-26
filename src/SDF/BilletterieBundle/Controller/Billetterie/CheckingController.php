<?php

namespace SDF\BilletterieBundle\Controller\Billetterie;

use Symfony\Component\HttpFoundation\Request;

use Ginger\Client\ApiException;

use SDF\BilletterieBundle\Controller\FrontController;
use SDF\BilletterieBundle\Entity\Billet;

class CheckingController extends FrontController
{
	public function checkTicketBarcodeValidityAction($barCode)
	{
		$em = $this->getDoctrine()->getManager();

		// Remove PDF generation additional number
		$trueBarcode = ($id - ($id % 10)) / 10;

		$ticket = $em->getRepository('SDFBilletterieBundle:Billet')->findOneBy(array('barcode' => $trueBarcode));

		if (!$ticket) {
			throw $this->createNotFoundException(sprintf('Impossible to find the ticket associated to barcode: %d', $trueBarcode));
		}

		return $this->renderJsonResponse(array(
			'validity' => $ticket->getValide(),
			'ticket' => $ticket
		));
	}

	public function retrieveTicketInformationsAction($id, Request $request)
	{
		$this->checkAppkey($request->query->get('key'));

		$em = $this->getDoctrine()->getManager();

		$ticket = $em->getRepository('SDFBilletterieBundle:Billet')->find($id);

		if (!$ticket) {
			throw $this->createNotFoundException(sprintf('Impossible to find the ticket: %d', $id));
		}

		$user = $ticket->getUser();

		if (!$ticket->getIsMajeur()) {
			$ticket = $this->checkIfUserIsAdult($ticket);
		}

		$this->instantLog($user, sprintf('Numéro associé au billet %d lu', $ticket->getId()));

		// TODO
		// Set up JmsSerializerBundle
		return $this->renderJsonResponse(array(
			'ticket' => $ticket,
			'user' => $ticket->getUser()
		));
	}

	public function getOwnerAssociatedTicketsAction($badgeId, Request $request)
	{
		// $this->checkAppkey($request->query->get('key'));
		$tickets = array();
		$message = '';

		$gingerClient = $this->get('ginger_client');
		$user = null;
		$userInfos = null;
		$isValid = false;
		$isAdult = false;

		try {
			$userInfos = $gingerClient->getCard($badgeId);
		}
		catch (ApiException $e) {}

		if ($userInfos) {
			$em = $this->getDoctrine()->getManager();

			$user = $em->getRepository('SDFBilletterieBundle:CasUser')->findOneByUsername($userInfos->login);

			if ($user) {
				$isValid = true;
				$user->setIsMajeur($userInfos->is_adulte);

				$em->persist($user);
				$em->flush();

				$isAdult = $user->getIsMajeur();

				$userTickets = $em->getRepository('SDFBilletterieBundle:Billet')->findBy(array('user' => $user));

				foreach ($userTickets as $userTicket) {
					if ($userTicket->getValide() && !$userTicket->getConsomme()) {
						$tickets[] = $userTicket;

						$this->instantLog($userTicket->getUser(), sprintf('Badge associé au billet %d scanné.', $ticket->getId()));
					}
				}
			} else {
				$message = 'L\'utilisateur n\'est pas dans la base de données.';
			}
		} else {
			$message = 'Ce badge n\'est pas reconnu par le service Ginger.';
		}

		return $this->renderJsonResponse(array(
			'user' => $user,
			'message' => $message,
			'isValide' => $isValid,
			'isAdulte' => $isAdult,
			'tickets' => $tickets
		));
	}

	public function checkInTicketAction($id, Request $request)
	{
		$this->checkAppkey($request->query->get('key'));
		$validity = true;
		$message = 'Bienvenue :)';

		$em = $this->getDoctrine()->getManager();
		$ticket = $em->getRepository('SDFBilletterieBundle:Billet')->find($id);

		if (!$ticket) {
			throw $this->createNotFoundException(sprintf('Impossible to find the ticket: %d', $id));
		}

		if (!$ticket->getValide()) {
			// Should not happen
			// User should never have received the mail, or even get a barcode.
			$validity = false;
			$message = 'Ce ticket est invalide (la transaction auprès de PayUtc n\'a pas été validée.';
		} elseif ($ticket->getConsomme()) {
			$validity = false;
			$message = 'Ce ticket à déjà été consommé.';
		} else {
			// TODO
			// Store a DateTime instead of a boolean!
			// So we would be able to say to the user when he's ticket would have been checked in...
			$ticket->setConsomme(true);
			$em->persist($ticket);
			$em->flush();
		}

		return $this->renderJsonResponse(array(
			'validity' => $validity,
			'message' => $message,
			'ticket' => $ticket
		));
	}

	protected function checkIfUserIsAdult(Billet $ticket)
	{
		// Recheck if ticket's user is_adulte, because he's birthdate could have occured since he bought the ticket
		// That's why we need to first compare it to the date event...
		// Except we can't do it for UTC students as Ginger does not provide the birthdate anyway.
		//
		// NOTE
		// It only checks if the ticket BUYER is adult, not the ticket OWNER...
		$user = $ticket->getUser();

		if ($user->isCasUser()) {
			$em = $this->getDoctrine()->getManager();

			// TODO
			// Set up GingerClient as a service
			$gingerClient = new GingerClient($this->container->getParameter('sdf_billetterie.ginger.key'), $this->container->getParameter('sdf_billetterie.ginger.url'));
			$userInfos = $gingerClient->getUser($user->getUsername());

			$ticket->setIsMajeur($userInfos->is_adulte);

			$em->persist($ticket);
			$em->flush();
		}

		return $ticket;
	}

	protected function checkAppkey($appKey)
	{
		$em = $this->getDoctrine()->getManager();

		if (!($appKey) || !($em->getRepository('SDFBilletterieBundle:Appkey')->findOneBy(array('relationKey' => $appKey)))) {
			throw $this->createAccessDeniedException();
		}
	}
}