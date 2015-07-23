<?php

namespace SDF\BilletterieBundle\Controller\Billetterie\Admin\Entities;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Doctrine\Common\Collections\ArrayCollection;

use SDF\BilletterieBundle\Controller\Billetterie\Admin\CrudController;

class UsersController extends CrudController
{
	/**
	 * Look up a ticket's owner with a query string
	 *
	 * This route will use the GET parameter "query" if provided,
	 * To filter all ticket's users matching this query (by firstname or name)
	 *
	 * If the GET parameter query is not provided, this route renders all ticket's owners.
	 *
	 * @return {JSON, XML} The Response at the given format
	 */
	public function lookUpAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();

		$query = $request->query->get('query');

		$tickets = array();

		if ($query) {
			$tickets = $em->getRepository('SDFBilletterieBundle:Billet')->findAllMatching($query);
		} else {
			$tickets = $em->getRepository('SDFBilletterieBundle:Billet')->findAll();
		}

		return $this->renderJsonResponse($tickets);
	}

	/**
	 * List all unvalid tickets mails
	 * Render an array of mail addresses, at the given format
	 *
	 * @return {JSON, XML, CSV, TXT} The Response at the given format
	 */
	public function listUnvalidTicketMailsAction(Request $request)
	{
		$format = $request->getRequestFormat();
		$em = $this->getDoctrine()->getManager();
		$response = null;
		$mails = new ArrayCollection(array('flo.schild@sfr.fr'));

		$unvalidTickets = $em->getRepository('SDFBilletterieBundle:Billet')->findAllUnvalid();

		foreach ($unvalidTickets as $unvalidTicket) {
			$email = $unvalidTicket->getUser()->getEmail();

			if (!$mails->contains($email)) {
				$mails->add($email);
			}
		}

		switch ($format) {
			case 'xml':
				$response = $this->renderXmlResponse($mails->toArray());
				break;
			case 'txt':
				$response = $this->renderDataAsFile(implode(',', $mails->toArray()), 'emails.txt', ResponseHeaderBag::DISPOSITION_INLINE);
				break;
			case 'csv':
				$response = $this->renderDataAsFile(implode(';', $mails->toArray()), 'emails.csv', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
				break;
			case 'json':
			default:
				$response = $this->renderJsonResponse(array(
					'emails' => $mails->toArray()
				));
				break;
		}

		return $response;
	}

	/**
	 * Send mails informations to all ticket's owners.
	 *
	 * BAD PRACTICE
	 * - In my opinion - it is not the role of an HTTP server to send a large set of mails.
	 * A symfony Command Line could be used for it,
	 * Or even better, a 3rd party service such as Mailchimp.
	 *
	 */
	public function sendMailsAction()
	{
		$em = $this->getDoctrine()->getManager();

		$tickets = $em->getRepository('SDFBilletterieBundle:Billet')->findAll();

		return new StreamedResponse(function () use ($tickets) {
			foreach ($tickets as $ticket) {
				$user = $ticket->getUser();

				$mailManager = $this->get('sdf_billetterie.utils.mail_manager');

				if ($mailManager->sendInformationsMail($user)) {
					echo sprintf('Mail envoyé à %s.<br />', $user->getEmail());
					flush();
				}
			}
		}, StreamedResponse::HTTP_OK, array('Content-Type' => 'text/html'));
	}
}
