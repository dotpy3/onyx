<?php

namespace SDF\BilletterieBundle\Controller\Pages\Admin\Entities;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Doctrine\Common\Collections\ArrayCollection;

use SDF\BilletterieBundle\Controller\Pages\Admin\CrudController;

class UsersController extends CrudController
{
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
	 * @return {CSV, JSON} The Response at the given format
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

	public function sendMailsAction()
	{

		send_time_limit(180);

		$em = $this->getDoctrine()->getManager();
		$repoUser = $em->getRepository('SDFBilletterieBundle:Utilisateur');
		$repoBillets = $em->getRepository('SDFBilletterieBundle:Billet');

		$listeUsers = $repoUser->findAll();

		foreach($listeUsers as $user){
			if ($user->getId() <= 606) break; else {
				$billet = $repoBillets->findOneBy(array('utilisateur' => $user));
				if (gettype($billet) != 'NULL'){
					$message = \Swift_Message::newInstance()->setSubject('Soirée des Finaux 2015 - Infos Pratiques')
								->setFrom('soireedesfinaux@assos.utc.fr')
								->setTo($user->getEmail())
								->setBody($this->renderView('SDFBilletterieBundle:Pages/Ticketing:mailinfos.html.twig'),'text/html');

					$this->get('mailer')->send($message);
					echo "fait pour : ".$user->getId()."<br />"; }
			}
		}
		return new Response("OK");
	}
}
