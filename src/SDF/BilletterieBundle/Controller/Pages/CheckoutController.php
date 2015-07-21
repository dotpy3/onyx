<?php

namespace SDF\BilletterieBundle\Controller\Pages;

use DateTime;

use Symfony\Component\HttpFoundation\Request;

use Payutc\Client\AutoJsonClient;
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

			$em->persist($ticket);

			$shuttle = $ticket->getNavette();

			if ($shuttle) {
				$remainingPlaces = $em->getRepository('SDFBilletterieBundle:Navette')->countRemainingPlaces($shuttle->getId());

				if ($remainingPlaces > 0) {
					$isShuttleValid = true;
				} else {
					$this->addFlash('error', 'Malheureusement, cette navette semble pleine :(');
				}
			}

			if ($isShuttleValid) {

				// TODO
				// Compare User's birthdate and Event start date !!
				$ticket->setIsMajeur(true); // Party on...
				$ticket->setConsomme(false);

				$ticket->setDateAchat(new DateTime());

				$barcodeGenerator = $this->get('sdf_billetterie.utils.barcode.generator');

				// Generate a unique barCode
				$ticket->setBarcode($barcodeGenerator->generateUniqueBarcode());

				// echo '<pre>';
				// exit(var_dump($ticket));

				$em->flush();

				$this->instantLog($user, 'Billet '. $ticket->getId() . ' créé dans la BDD');
				// $this->addFlash('success', 'Votre billet à bien été commandé. Il est en attente de la validation de PayUtc');

				$payutcClient = new AutoJsonClient("https://api.nemopay.net/services/", "WEBSALE", array(CURLOPT_PROXY => 'proxyweb.utc.fr:3128', CURLOPT_TIMEOUT => 5), "Payutc Json PHP Client", array(), "payutc", $this->container->getParameter('sdf_billetterie.payutc.key'));
				$item = json_encode(array(array($price->getIdPayutc())));

				try {
					// TODO
					// USE A SERVICE HERE !!!
					// CONNEXION A PAYUTC
					// Need some informations about PayUtc API here.
					$call = $payutcClient->apiCall('createTransaction', array(
						'fun_id' => $this->container->getParameter('sdf_billetterie.payutc.fundation_id'),
						'items' => $item,
						'return_url' => $this->generateUrl('sdf_billetterie_routingPostPaiement', array('id' => $ticket->getId()), true),
						'callback_url' => $this->generateUrl('sdf_billetterie_callbackDePAYUTC', array('id' => $ticket->getId()), true),
						'mail' => $user->getEmail()
					));
				} catch (JsonException $e) {
					$this->instantLog($user, 'La connexion à Payutc a échoué pour valider l\'achat du billet '. $ticket->getId());

					// If the transaction fail, what should be the ticket idPayutc ???
					// $ticket->setIdPayutc(...) ???

					// Why removing the ticket ?
					// Seems to be possible to retry the transaction ?

					// $em->remove($billetCree);
					// $em->flush();

					$this->addFlash('danger', 'La connexion à Payutc a échoué pour valider l\'achat du billet. Veuillez réessayer la transaction.');
				}

				$ticket->setIdPayutc($call->tra_id);

				$em->persist($ticket);
				$em->flush();

				$this->instantLog($user, 'Connexion réussie à PayUtc pour valider l\'achat du billet ' . $ticket->getId() . ' associé à l\'identifiant Payutc '. $call->tra_id);
				$this->addFlash('success', 'Connexion réussie à PayUtc pour valider l\'achat du billet.');


				return $this->redirect($call->url);
				// return $this->redirectToRoute('sdf_billetterie_homepage');
			}
		}

		return $this->render('SDFBilletterieBundle:Pages/Ticketing/Checkout:order.html.twig', array(
			'form' => $form->createView(),
			'price' => $price,
			'shuttles' => $shuttles
		));
	}
}