<?php

namespace SDF\BilletterieBundle\Controller\Pages;

use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Controller\FrontController;
use SDF\BilletterieBundle\Form\BilletOrderType;
use SDF\BilletterieBundle\Exception\ImageNotFoundException;

class TicketingController extends FrontController
{
	public function retryTransactionAction($id)
	{
		$ticket = $this->findTicket($id);

		if ($ticket->getValide()) {
			$this->addFlash('info', 'Votre ticket est bien validé.');

			return $this->redirectToRoute('sdf_billetterie_homepage');
		}

		$config = $this->container->getParameter('sdf_billetterie');

		return $this->redirect($this->container->getParameter('sdf_billetterie.nemopay.payment_url') . '/validation?tra_id=' . $ticket->getIdPayutc());
	}

	public function cancelTransactionAction($id)
	{
		$ticket = $this->findTicket($id);

		if ($ticket->getValide()) {
			$this->addFlash('info', 'Le billet à déjà été validé.');

			return $this->redirectToRoute('sdf_billetterie_homepage');
		}

		$em = $this->getDoctrine()->getManager();

		$em->remove($ticket);
		$em->flush();

		$this->addFlash('success', 'Le billet a bien été annulé.');

		return $this->redirectToRoute('sdf_billetterie_homepage');
	}

	public function validateAction($id)
	{
		$ticket = $this->findTicket($id);

		if ($ticket->getValide()) {
			$this->addFlash('success', 'Votre commande est validée :)');
		} else {
			$this->addFlash('danger', 'Une erreur s\'est produite pendant la validation de votre commande...');
		}

		return $this->redirectToRoute('sdf_billetterie_homepage');
	}

	public function editTicketAction($id)
	{
		$ticket = $this->findTicket($id);
		$em = $this->getDoctrine()->getManager();

		// Get all shuttles which still have places on board
		$shuttles = $em->getRepository('SDFBilletterieBundle:Navette')->findAllWithRemainingPlaces();

		// Count the remaining places in order to display it (@see Navette::__toString())
		$shuttles = array_map(function ($shuttle) use ($em) {
			$shuttle->setRemainingPlaces($em->getRepository('SDFBilletterieBundle:Navette')->countRemainingPlaces($shuttle->getId()));

			return $shuttle;
		}, $shuttles);

		$form = $this->createForm(new BilletOrderType($shuttles), $ticket);

		return $this->render('SDFBilletterieBundle:Pages/Ticketing/Ticket:edit.html.twig', array(
			'ticket' => $ticket,
			'form' => $form->createView()
		));
	}

	public function updateTicketAction($id, Request $request)
	{
		$ticket = $this->findTicket($id);
		$em = $this->getDoctrine()->getManager();

		// Get all shuttles which still have places on board
		$shuttles = $em->getRepository('SDFBilletterieBundle:Navette')->findAllWithRemainingPlaces();

		// Count the remaining places in order to display it (@see Navette::__toString())
		$shuttles = array_map(function ($shuttle) use ($em) {
			$shuttle->setRemainingPlaces($em->getRepository('SDFBilletterieBundle:Navette')->countRemainingPlaces($shuttle->getId()));

			return $shuttle;
		}, $shuttles);

		$form = $this->createForm(new BilletOrderType($shuttles), $ticket);
		$form->handleRequest($request);

		if ($form->isValid()) {
			$em = $this->getDoctrine()->getManager();

			$em->persist($ticket);
			$em->flush();

			$this->addFlash('success', 'Le billet à bien été modifié :)');

			return $this->redirectToRoute('sdf_billetterie_ticket_edit', array('id' => $ticket->getId()));
		}

		return $this->render('SDFBilletterieBundle:Pages/Ticketing/Ticket:edit.html.twig', array(
			'ticket' => $ticket,
			'form' => $form->createView()
		));
	}

	public function printTicketAction($id)
	{
		$ticket = $this->findTicket($id);
		$pdfGenerator = $this->get('sdf_billetterie.utils.pdf.generator');

		// The PdfGenerator::generateTicket method might throw two exceptions
		// A NullTicketException, if the ticket is null (should not happen here, as an exception would already have been triggered by the findTicket() method)
		// A ImageNotFoundException, if the background-image used for the PDF cannot be opened
		// (check the app/config/parameters.yml file in this case)
		try {
			$pdf = $pdfGenerator->generateTicket($ticket);
		}
		catch (ImageNotFoundException $e) {
			throw $this->createNotFoundException('Une erreur est survenue lors de l\'impression de votre ticket. Veuillez contacter un administrateur du site.');
		}

		return $this->renderDataAsFile($pdf, 'ticket.pdf');
	}
}
