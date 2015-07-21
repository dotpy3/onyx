<?php

namespace SDF\BilletterieBundle\Controller\Pages;

use DateTime;

use Symfony\Component\HttpFoundation\Request;

use Payutc\Client\JsonException;

use SDF\BilletterieBundle\Controller\FrontController;
use SDF\BilletterieBundle\Entity\Billet;
use SDF\BilletterieBundle\Form\BilletOrderType;

class CheckoutController extends FrontController
{
	public function checkoutTicketAction($priceId)
	{
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$price = $em->getRepository('SDFBilletterieBundle:Tarif')->find($priceId);

		if (!$price) {
			throw $this->createNotFoundException('Le tarif demandé est introuvable.');
		}

		// Get all shuttles which still have places on board
		$shuttles = $em->getRepository('SDFBilletterieBundle:Navette')->findAllWithRemainingPlaces();

		// Count the remaining places in order to display it (@see Navette::__toString())
		$shuttles = array_map(function ($shuttle) use ($em) {
			$shuttle->setRemainingPlaces($em->getRepository('SDFBilletterieBundle:Navette')->countRemainingPlaces($shuttle->getId()));

			return $shuttle;
		}, $shuttles);

		$ticket = new Billet();
		$ticket->setTarif($price);
		$ticket->setUser($user);
		$form = $this->createForm(new BilletOrderType($shuttles), $ticket);

		return $this->render('SDFBilletterieBundle:Pages/Ticketing/Checkout:order.html.twig', array(
			'form' => $form->createView(),
			'price' => $price,
			'shuttles' => $shuttles
		));
	}

	public function checkoutTicketCheckAction($priceId, Request $request)
	{
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$price = $em->getRepository('SDFBilletterieBundle:Tarif')->find($priceId);

		if (!$price) {
			throw $this->createNotFoundException('Le tarif demandé est introuvable.');
		}

		// Get all shuttles which still have places on board
		$shuttles = $em->getRepository('SDFBilletterieBundle:Navette')->findAllWithRemainingPlaces();

		// Count the remaining places in order to display it (@see Navette::__toString())
		$shuttles = array_map(function ($shuttle) use ($em) {
			$shuttle->setRemainingPlaces($em->getRepository('SDFBilletterieBundle:Navette')->countRemainingPlaces($shuttle->getId()));

			return $shuttle;
		}, $shuttles);

		$ticket = new Billet();
		$ticket->setTarif($price);
		$ticket->setUser($user);
		$form = $this->createForm(new BilletOrderType($shuttles), $ticket);

		$form->handleRequest($request);

		if ($form->isValid()) {
			$isShuttleValid = true;

			$shuttle = $ticket->getNavette();

			if ($shuttle) {
				// If the user wants to take a shuttle, check its availability
				$remainingPlaces = $em->getRepository('SDFBilletterieBundle:Navette')->countRemainingPlaces($shuttle->getId());

				if ($remainingPlaces > 0) {
					$isShuttleValid = true;
				} else {
					$this->addFlash('error', 'Malheureusement, cette navette semble pleine :(');
				}
			}

			if ($isShuttleValid) {

				// TODO
				// Compare User's birthdate and the event start date !!
				// Need a Model's evolution
				// During that time... Party on!
				$ticket->setIsMajeur(true);
				$ticket->setDateAchat(new DateTime());

				// Generate a unique barCode
				$barcodeGenerator = $this->get('sdf_billetterie.utils.barcode.generator');
				$ticket->setBarcode($barcodeGenerator->generateUniqueBarcode());

				$em->persist($ticket);
				$em->flush();

				$this->instantLog($user, 'Billet '. $ticket->getId() . ' créé dans la BDD');
				// $this->addFlash('success', 'Votre billet à bien été commandé. Il est en attente de la validation de PayUtc');

				$payutcClient = $this->get('payutc_client');

				try {
					// CONNEXION A PAYUTC
					// Need some informations about PayUtc API...
					$call = $payutcClient->apiCall('createTransaction', array(
						'fun_id' => $this->container->getParameter('sdf_billetterie.payutc.fundation_id'),
						'items' => json_encode(array($price->getIdPayutc() => 1)),
						'return_url' => $this->generateUrl('sdf_billetterie_checkout_ticket_validate', array('id' => $ticket->getId()), true),
						'callback_url' => $this->generateUrl('sdf_billetterie_checkout_ticket_payutc_callback', array('id' => $ticket->getId()), true),
						'mail' => $user->getEmail()
					));
				}
				catch (JsonException $e) {
					$this->instantLog($user, 'La connexion à Payutc a échoué pour valider l\'achat du billet '. $ticket->getId() . '. Error: ' . $e->getMessage());

					// If the transaction fail, what should be the ticket idPayutc ???
					// $ticket->setIdPayutc(...) ???

					// Why remove the ticket ?
					// Seems to be possible to retry the transaction ?

					// $em->remove($ticket);
					// $em->flush();

					$this->addFlash('danger', 'La connexion à Payutc a échoué pour valider l\'achat du billet. Veuillez réessayer la transaction.');

					return $this->redirectToRoute('sdf_billetterie_homepage');
				}

				// The transaction has been created
				$ticket->setIdPayutc($call->tra_id);

				$em->persist($ticket);
				$em->flush();

				$this->instantLog($user, 'Connexion réussie à PayUtc pour valider l\'achat du billet ' . $ticket->getId() . ' associé à l\'identifiant Payutc '. $call->tra_id);
				$this->addFlash('success', 'Connexion réussie à PayUtc pour valider l\'achat du billet.');

				// Redirect to PayUTC to continue the payment process
				return $this->redirect($call->url);
			}
		}

		return $this->render('SDFBilletterieBundle:Pages/Ticketing/Checkout:order.html.twig', array(
			'form' => $form->createView(),
			'price' => $price,
			'shuttles' => $shuttles
		));
	}

	public function handlePayutcCallback($id)
	{
		$ticket = $this->findTicket($id);
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();

		// CONNEXION A PAYUTC
		$payutcClient = $this->get('payutc_client');

		$data = $payutcClient->apiCall('getTransactionInfo', array(
			'fun_id' => $this->container->getParameter('sdf_billetterie.payutc.fundation_id'),
			'tra_id' => $ticket->getIdPayutc()
		));

		switch ($data->status) {
			case 'V':
				$ticket->setValide(true);

				$em->persist($ticket);
				$em->flush();

				$mailManager = $this->get('sdf_billetterie.utils.mail_manager');
				$mailManager->sendConfirmationMail($user);

				$this->instantLog($user, sprintf('Ticket %d validé par Payutc.', $ticket->getId()));
				$this->addFlash('success', 'Votre ticket a bien été validé, vous devriez le recevoir très bientôt par mail.');
				break;
			case 'A':
			default:
				$em->remove($ticket);
				$em->flush();

				$this->instantLog($user, sprintf('Ticket %d invalidé par Payutc, paiement annulé.', $ticket->getId()));
				$this->addFlash('warning', 'Votre ticket n\'a pas pu être validé.');
				break;
		}

		return $this->redirectToRoute('sdf_billetterie_homepage');
	}
}