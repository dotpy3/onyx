<?php

namespace SDF\BilletterieBundle\Controller\Pages;

use Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Controller\FrontController;
use SDF\BilletterieBundle\Form\BilletOrderType;
use SDF\BilletterieBundle\Exception\UserNotFoundException;

class TicketingController extends FrontController
{

	// INSERT YOUR PARAMETERS HERE :
	private $PDOdatabase = "";

	private $PDOhost = "";

	private $user = '';
	private $password = '';

	private $email = ""; // l'email de l'asso

	// END OF PARAMETER INSERTION

	private function automatedJsonResponse(array $data)
	{
		// TAKES AN ARRAY AS A PARAMETER
		return new JsonResponse($data);
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
		// A NullTicketException, if the ticket is null (should not happen here, as an exception would already have been triggered by findTicket())
		// A ImageNotFoundException, if the background-image used for the PDF cannot be opened
		// (check the app/config/parameters.yml file in this case)
		try {
			$pdf = $pdfGenerator->generateTicket($ticket);
		}
		catch (NullTicketException $e) {
			throw $this->createNotFoundException('Impossible de trouver ce ticket.');
		}

		return $this->renderDataAsFile($pdf, 'ticket.pdf');
	}

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

	public function getEmailsNonValideAction(){

			$em=$this->getDoctrine()->getManager();
			$repoBillets = $em->getRepository('SDFBilletterieBundle:Billet');

			$requete = $em->createQuery('SELECT u FROM SDFBilletterieBundle:Billet b JOIN b.user u WHERE b.valide = FALSE')
							->getResult();

			$reponse = "";
			foreach($requete as $user){
					$reponse .= ", ".$user->getEmail();
			}
			return new Response($reponse);
	}

	public function checkByNamePortionAction($name){

		$em = $this->getDoctrine()->getManager();

		$repoBillets = $em->getRepository('SDFBilletterieBundle:Billet');
		$repoKeys = $em->getRepository('SDFBilletterieBundle:Appkey');

		if(!isset($name)) return automatedJsonResponse(array('isValide' => 'unsetName'));

		if (!isset($_GET['key']) || gettype($repoKeys->findOneBy(array('relationKey' => $_GET['key']))) == 'NULL')
			return automatedJsonResponse(array('isValide' => 'invalidKey'));

		$bdd = new \PDO('mysql:host='.$this->PDOhost.';dbname='.$this->PDOdatabase.';charset=utf8',$this->user,$this->password);
		$requete = "SELECT id, nom, prenom, valide, consomme FROM Billet WHERE prenom LIKE %$name% OR nom LIKE %$name%";

		$resultat = $bdd->query($requete);
		$billetsAffiches = array('isValide' => 'yes');
		$i=0;

		while($donnees = $resultat->fetch()){
			if ($donnees['valide'] && !$donnees['consomme']){
									$billetsAffiches[$i++] = array('id' => $donnees['id'],
									'nom' => $donnees['nom'],
									'prenom' => $donnees['prenom']);
								}
		}

		return automatedJsonResponse($billetsAffiches);

	}

	public function sendMailInfosAction(){

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

	private function checkUserExists()
	{
		// VERIFIE QU'IL Y A BIEN UN UTILISATEUR DE CONNECTE
		// SINON, JETTE UNE ERREUR NOTFOUNDUSEREXCEPTION
		return !is_null($this->getUser());
	}

	private function checkUserIsAdmin(){
		// RETOURNE TRUE SI L'USER EST ADMIN
		// RETOURNE FALSE S'IL N'Y A PAS D'USER CONNECTÉ OU S'IL N'EST PAS ADMIN
		return in_array('ROLE_ADMIN', $this->getUser()->getRoles());
	}
}
