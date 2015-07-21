<?php

namespace SDF\BilletterieBundle\Controller\Pages;

use Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Constraints\Date;

use SDF\BilletterieBundle\Controller\FrontController;
use SDF\BilletterieBundle\Entity\User;
use SDF\BilletterieBundle\Entity\CasUser;
use SDF\BilletterieBundle\Entity\Evenement;
use SDF\BilletterieBundle\Entity\Tarif;
use SDF\BilletterieBundle\Entity\Navette;
use SDF\BilletterieBundle\Entity\Billet;
use SDF\BilletterieBundle\Entity\Trajet;
use SDF\BilletterieBundle\Entity\Contraintes;
use SDF\BilletterieBundle\Entity\Log;
use SDF\BilletterieBundle\Entity\Appkey;
use SDF\BilletterieBundle\Entity\PotCommunTarifs;
use SDF\BilletterieBundle\Form\TarifType;
use SDF\BilletterieBundle\Form\TrajetType;
use SDF\BilletterieBundle\Form\NavetteType;
use SDF\BilletterieBundle\Form\BilletType;
use SDF\BilletterieBundle\Form\BilletOrderType;
use SDF\BilletterieBundle\Form\PotCommunTarifsType;
use SDF\BilletterieBundle\Exception\UserNotFoundException;
use SDF\BilletterieBundle\Utils\Pdf\Pdf;

use \Payutc\Client\AutoJsonClient;
use \Payutc\Client\JsonException;

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

			$this->addFlash('success', 'Le billet à bien été modifié!');

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
		// A NullTicketException, if the ticket is null (should not arrive)
		// A ImageNotFoundException, if the background-image used for the PDF cannot be opened
		// (check the app/config/parameters.yml file in this case)
		try {
			$pdf = $pdfGenerator->generateTicket($ticket);
		}
		catch (NullTicketException $e) {
			throw $this->createNotFoundException('Impossible de trouver ce ticket...');
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

	public function checkContraintesAction(Request $request){

			// ON VERIFIE QUE L'UTILISATEUR EXISTE & EST ADMIN
			if (!$this->checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));


			$event = new Contraintes();
			$formBuilder = $this->get('form.factory')->createBuilder('form', $event);
			$formBuilder
				->add('nom',      'text')
				->add('doitEtreCotisant',     'checkbox')
				->add('doitNePasEtreCotisant',     'checkbox')
				->add('accessibleExterieur', 'checkbox')
				->add('debutMiseEnVente', 'datetime')
				->add('finMiseEnVente', 'datetime')
			->add('save',      'submit')
			;
			$form = $formBuilder->getForm();

			$form->handleRequest($request);
			if ($form->isValid()) {
					$em = $this->getDoctrine()->getManager();
					$em->persist($event);
					$em->flush();

					return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
				'form' => $form->createView(),'name' => "set de contraintes", 'addError' => false, 'addOK' => true
			));
			}


			return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
				'form' => $form->createView(),'name' => "set de contraintes", 'addError' => false, 'addOK' => false
			));
	}

	public function checkEventAction(Request $request){

			// ON VERIFIE QUE L'UTILISATEUR EXISTE & EST ADMIN
			if (!$this->checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));

			$event = new Evenement();
			$formBuilder = $this->get('form.factory')->createBuilder('form', $event);
			$formBuilder
				->add('nom',      'text')
				->add('quantiteMax',     'text')
			->add('save',      'submit')
			;
			$form = $formBuilder->getForm();

			$form->handleRequest($request);
			if ($form->isValid()) {
					$em = $this->getDoctrine()->getManager();
					$em->persist($event);
					$em->flush();

					return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
				'form' => $form->createView(),'name' => "évènement", 'addError' => false, 'addOK' => true
			));
			}


			return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
				'form' => $form->createView(),'name' => "évènement", 'addError' => false, 'addOK' => false
			));
	}

	public function tarifsAction(Request $request){

			// ON VERIFIE QUE L'UTILISATEUR EXISTE & EST ADMIN
			if (!$this->checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));

			$tarif = new Tarif();

			$form = $this->get('form.factory')->create(new TarifType, $tarif);

			if($form->handleRequest($request)->isValid()){
					$em = $this->getDoctrine()->getManager();
					$em->persist($tarif);
					$em->flush();

					return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
					'form' => $form->createView(),'name' => "tarif", 'addError' => false, 'addOK' => true
				));

			}

			return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
				'form' => $form->createView(),'name' => "tarif", 'addError' => false, 'addOK' => false
			));

	}

	public function checkTrajetsNavetteAction(Request $request){

			// ON VERIFIE QUE L'UTILISATEUR EXISTE & EST ADMIN
			if (!$this->checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));

			$tarif = new Trajet();

			$form = $this->get('form.factory')->create(new TrajetType, $tarif);

			if($form->handleRequest($request)->isValid()){
					$em = $this->getDoctrine()->getManager();
					$em->persist($tarif);
					$em->flush();

					return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
				'form' => $form->createView(),'name' => "trajet", 'addError' => false, 'addOK' => true
			));

			}

			return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
				'form' => $form->createView(),'name' => "trajet", 'addError' => false, 'addOK' => false
			));
	}

	public function checkNavettesAction(Request $request){

			// ON VERIFIE QUE L'UTILISATEUR EXISTE & EST ADMIN
			if (!$this->checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));

			$tarif = new Navette();

			$form = $this->get('form.factory')->create(new NavetteType, $tarif);

			if($form->handleRequest($request)->isValid()){
					$em = $this->getDoctrine()->getManager();
					$em->persist($tarif);
					$em->flush();

					return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
				'form' => $form->createView(),'name' => "navette", 'addError' => false, 'addOK' => true
			));

			}

			return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
				'form' => $form->createView(),'name' => "navette", 'addError' => false, 'addOK' => false
			));
	}


	public function checkPotCommunAction(Request $request){

			// ON VERIFIE QUE L'UTILISATEUR EXISTE & EST ADMIN
			if (!$this->checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));

			$pot = new PotCommunTarifs();

			$form = $this->get('form.factory')->create(new PotCommunTarifs, $tarif);

			if($form->handleRequest($request)->isValid()){
					$em = $this->getDoctrine()->getManager();
					$em->persist($tarif);
					$em->flush();

					return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
				'form' => $form->createView(),'name' => "pot commun", 'addError' => false, 'addOK' => true
			));

			}

			return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
				'form' => $form->createView(),'name' => "pot commun", 'addError' => false, 'addOK' => false
			));
	}

	public function billetAdminAction(Request $request){

			// ON VERIFIE QUE L'UTILISATEUR EXISTE & EST ADMIN
			if (!$this->checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));

			$tarif = new Billet();

			$form = $this->get('form.factory')->create(new BilletType, $tarif);

			if($form->handleRequest($request)->isValid()){
					$em = $this->getDoctrine()->getManager();
					$em->persist($tarif);
					$em->flush();

					return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
				'form' => $form->createView(),'name' => "billet", 'addError' => false, 'addOK' => true
			));

			}

			return $this->render('SDFBilletterieBundle:Pages/Ticketing:add.html.twig', array(
				'form' => $form->createView(),'name' => "billet", 'addError' => false, 'addOK' => false
			));
	}

	public function buyBilletAction($typeBillet){

			/*        ON COMMENCE PAR VÉRIFIER LA CONNEXION        */

			try {
				$this->checkUserExists();
				if ($_SESSION['usertype'] == 'cas') $userActif = $em->getRepository('SDFBilletterieBundle:CasUser')
							->findOneBy(array('loginCAS' => $login));
				else $userActif = $em->getRepository('SDFBilletterieBundle:User')
							->findOneBy(array('login' => $login));
				$userRefActif = $userActif->getUser();
				/* LA CONNEXION EST VÉRIFIÉE        ON VÉRIFIE LES ACCÈS AU BILLET */
				if (!checkBilletAvailable($typeBillet)) return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));
			} catch (UserNotFoundException $e) {
				return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
			}

			/*

					ACCES AU BILLET VERIFIE

			*/

			$repoNavettes = $em->getRepository('SDFBilletterieBundle:Navette');

			$arrayToutesNavettes = $repoNavettes->findAll();
			$tabNavettes = Array();
			foreach($arrayToutesNavettes as $navetteEtudiee){
					$enregNavette = Array();
					$enregNavette['idNavette'] = $navetteEtudiee->getId();
					$enregNavette['lieuDepart'] = $navetteEtudiee->getTrajet()->getLieuDepart();
					$enregNavette['horaireNavette'] = $navetteEtudiee->getHoraireDepartFormat();
					$requete = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.navette n WHERE n.id = :idNavette')
							->setParameter('idNavette',$navetteEtudiee->getId())
							->getResult();
					if ($requete[0]['c'] < $navetteEtudiee->getCapaciteMax()){
							$enregNavette['desactivee'] = false;
					} else {
							$enregNavette['desactivee'] = true;
					}
					$enregNavette['placesRestantes'] = - ($requete[0]['c'] - $navetteEtudiee->getCapaciteMax());
					$tabNavettes[] = $enregNavette;
			}

			return $this->render('SDFBilletterieBundle:Pages/Ticketing:achatbillet.html.twig', array(
				'billetID' => $billetDispo->getId(),
				'typeBillet' => $billetDispo->getNomTarif(),
				'prixBillet' => $billetDispo->getPrix(),
				'navettes' => $tabNavettes
			));
	}

	public function payUTCcallbackAction($token)
	{
			$config = $this->container->getParameter('sdf_billetterie');
			/*

			ON COMMENCE PAR VÉRIFIER LA CONNEXION

			*/

			/*        ON COMMENCE PAR VÉRIFIER LA CONNEXION        */

			try {
				$this->checkUserExists();
				if ($_SESSION['usertype'] == 'cas') $userActif = $em->getRepository('SDFBilletterieBundle:CasUser')
							->findOneBy(array('loginCAS' => $login));
				else $userActif = $em->getRepository('SDFBilletterieBundle:User')
							->findOneBy(array('login' => $login));
				$userRefActif = $userActif->getUser();
				/* LA CONNEXION EST VÉRIFIÉE        ON VÉRIFIE LES ACCÈS AU BILLET */
				if (!checkBilletAvailable($token)) return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));
			} catch (UserNotFoundException $e) {
				return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
			}

			/*

					ACCES AU BILLET VERIFIE

			*/

					/* On vérifie maintenant les données */

					if (!isset($_POST['nom']) || $_POST['nom'] == ''){
							return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));
					}
					if (!isset($_POST['prenom']) || $_POST['prenom'] == ''){
							return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));
					}


					$repoNavettes = $em->getRepository('SDFBilletterieBundle:Navette');
					if (isset($_POST['sel1']) && $_POST['sel1'] = 'noNavette') $navetteChoisie = 'noNavette';
					else {
							if (isset($_POST['sel1']) && gettype($em->getRepository('SDFBilletterieBundle:Navette')->find($_POST['sel1'])) == 'NULL'){
									// Navette définie, et existe : on vérifie qu'il y a assez de place
									$requete = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.navette n WHERE n.id = :idNavette')
									->setParameter('idNavette',$_POST['sel1'])
									->getResult();
									if ($requete[0]['c'] < $repoNavettes->find($_POST['sel1'])){
											// PLACE VALIDE
											// ON ATTRIBUE LA PLACE DANS LA NAVETTE
											$navetteChoisie = $_POST['sel1'];
											// OK
									} else {
											return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));
									}

							} else {
									return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));
							}
					}

					/* OK DONNEES VERIFIEES */

					/* On passe à la génération du billet */

					$billetCree = new Billet();
					$billetCree->setValide(false);
					$billetCree->setIdPayutc('');
					$billetCree->setNom($_POST['nom']);
					$billetCree->setPrenom($_POST['prenom']);
					$billetCree->setIsMajeur(true);
					$billetCree->setConsomme(false);
					$billetCree->setDateAchat(new \DateTime());
					$notOK = true;
					while($notOK){
							$barcode = rand(0,1000000000);
							if(gettype($em->getRepository('SDFBilletterieBundle:Billet')->findOneBy(array('barcode' => $barcode))) == 'NULL') $notOK = false;
					}
					$billetCree->setBarcode($barcode);
					if(isset($_POST['droitimage']) && $_POST['droitimage'] == '1'){
							$billetCree->setAccepteDroitImage(true);
					} else{
							$billetCree->setAccepteDroitImage(false);
					}
					if($navetteChoisie != 'noNavette') $billetCree->setNavette($em->getRepository('SDFBilletterieBundle:Navette')->find($navetteChoisie));
					$billetCree->setTarif($em->getRepository('SDFBilletterieBundle:Tarif')->find($id));
					$billetCree->setUtilisateur($userRefActif);

					$em->persist($billetCree);
					$em->flush();

					$this->instantLog($userRefActif, "Billet ".$billetCree->getId()." généré dans la BDD pour l'user ");

					try {
							// CONNEXION A PAYUTC
							$payutcClient = new AutoJsonClient("https://api.nemopay.net/services/", "WEBSALE", array(CURLOPT_PROXY => 'proxyweb.utc.fr:3128', CURLOPT_TIMEOUT => 5), "Payutc Json PHP Client", array(), "payutc", $config['payutc']['key']);

							$arrayItems = array(array($billetDispo->getIdPayutc()));
							$item = json_encode($arrayItems);
							//return new Response($item);
							$billetIds = array();
							$billetIds[] = $billetCree->getTarif()->getIdPayutc();
							$returnURL = 'http://' . $_SERVER["HTTP_HOST"].$this->get('router')->generate('sdf_billetterie_routingPostPaiement',array('id'=>$billetCree->getId()));
							$callback_url = 'http://' . $_SERVER["HTTP_HOST"].$this->get('router')->generate('sdf_billetterie_callbackDePAYUTC',array('id'=>$billetCree->getId()));
							//return new Response($item);
							$c = $payutcClient->apiCall('createTransaction', array(
								"fun_id" => $config['payutc']['fundation_id'],
								"items" => $item,
								"return_url" => $returnURL,
								"callback_url" => $callback_url,
								"mail" => $userRefActif->getEmail()
							));

							$billetCree->setIdPayutc($c->tra_id);

							$em->persist($billetCree);
							$em->flush();

							$this->instantLog($userRefActif, "Connexion réussie à Payutc dans le cadre de l'achat du billet ".$billetCree->getId()." associé à l'identifiant Payutc ".$c->tra_id);


							return $this->redirect($c->url);
					} catch (JsonException $e){


							$log1 = new Log();
							$log1->setUser($userRefActif);
							$log1->setContent("Connexion à Payutc a échoué dans le cadre de l'achat du billet ".$billetCree->getId());
							$log1->setDate(new \DateTime());

							$em->persist($log1);
							$em->flush();

							$em->remove($billetCree);
							$em->flush();

							//return new Response($e);
							return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));
					}
	}

	public function callbackFromPayutcAction($id)
	{
			$config = $this->container->getParameter('sdf_billetterie');
			$em = $this->getDoctrine()->getManager();
			$repoBillets = $em->getRepository('SDFBilletterieBundle:Billet');
			$billet = $repoBillets->find($id);
			if (gettype($billet) == 'NULL') return new Response("échec d'obtention du billet");
			//if ($billet->getValide() == true) return new Response("déjà validé");

			// CONNEXION A PAYUTC
			$payutcClient = new AutoJsonClient("https://api.nemopay.net/services/", "WEBSALE", array(CURLOPT_PROXY => 'proxyweb.utc.fr:3128', CURLOPT_TIMEOUT => 5), "Payutc Json PHP Client", array(), "payutc", $config['payutc']['key']);
			$data = $payutcClient->apiCall('getTransactionInfo', array(
				'fun_id' => $config['payutc']['fundation_id'],
				'tra_id' => $billet->getIdPayutc()
			));

			if ($data->status == "V"){
					$billet->setValide(true);
					$em->persist($billet);

					$log1 = new Log();
					$log1->setUser($billet->getUtilisateur());
					$log1->setContent("Billet ".$billet->getId()." validé par Payutc : payé ");
					$log1->setDate(new \DateTime());

					$em->persist($log1);
					$em->flush();

					$message = \Swift_Message::newInstance()->setSubject('Votre billet pour la Soirée des Finaux 2015')
							->setFrom($email)
							->setTo($billet->getUtilisateur()->getEmail())
							->setBody($this->renderView('SDFBilletterieBundle:Pages/Ticketing:mailenvoi.html.twig'),'text/html');

					$this->get('mailer')->send($message);

			} elseif ($data->status == 'A') {

					$log1 = new Log();
					$log1->setUser();
					$log1->setContent("Billet ".$billet->getId()." invalidé par Payutc : non payé, paiement avorté ");
					$log1->setDate(new \DateTime());

					$em->persist($log1);
					$em->flush();

					$em->remove($billet);
					$em->flush();
			} else {
					$log1 = new Log();
					$log1->setUser();
					$log1->setContent("Billet ".$billet->getId()." invalidé par Payutc : non payé, paiement non abouti ");
					$log1->setDate(new \DateTime());

					$em->persist($log1);
					$em->flush();

					$em->remove($billet);
					$em->flush();
			}

			var_dump($data);




			$em->flush();

			return new Response('ok');
	}

	public function callbackFromPayutcByIdAction($id)
	{
			$config = $this->container->getParameter('sdf_billetterie');
			$payutcClient = new AutoJsonClient("https://api.nemopay.net/services/", "WEBSALE", array(CURLOPT_PROXY => 'proxyweb.utc.fr:3128', CURLOPT_TIMEOUT => 5), "Payutc Json PHP Client", array(), "payutc", $config['payutc']['key']);
			$data = $payutcClient->apiCall('getTransactionInfo', array(
				'fun_id' => $config['payutc']['fundation_id'],
				'tra_id' => $id
			));

			var_dump($data);


			return new Response('ok');
	}

	public function routingPostPayAction($id){
			$em = $this->getDoctrine()->getManager();
			$repoBillets = $em->getRepository('SDFBilletterieBundle:Billet');

			$billet = $repoBillets->find($id);

			if(gettype($billet) == 'NULL') return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));

			if($billet->getValide()) return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'billetSuccess')));

			else return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));
	}

	public function testbugAction(){
			// FONCTION CREEE POUR TESTER L'URL DE REDIRECTION VERS LA TRANSACTION
			$config = $this->container->getParameter('sdf_billetterie');

			$payutcClient = new AutoJsonClient("https://api.nemopay.net/services/", "WEBSALE", array(CURLOPT_PROXY => 'proxyweb.utc.fr:3128', CURLOPT_TIMEOUT => 5), "Payutc Json PHP Client", array(), "payutcdev", $config['payutc']['key']);

							$arrayItems = array(array(3201));
							$item = json_encode($arrayItems);
							//return new Response($item);
							$returnURL = 'http://google.fr/test';
							$callback_url = 'http://google.fr/test';
							//return new Response($item);
							$c = $payutcClient->apiCall('createTransaction', array(
								"fun_id" => $config['payutc']['fundation_id'],
								"items" => $item,
								"return_url" => $returnURL,
								"callback_url" => $callback_url,
								"mail" => 'ericgourlaouen@airpost.net'
							));

							return new Response($c->url);
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

	public function checkValidBarcodeAction($id){
		$trueBarcode = ($id-($id%10))/10;
		$em=$this->getDoctrine()->getManager();
		$repoBillets = $em->getRepository('SDFBilletterieBundle:Billet');
		$billet = "";
		$billet = $repoBillets->findOneBy(array('barcode' => $trueBarcode));

		return new Response(var_dump($billet->getValide()));
	}

	public function checkValidNumBilletAction($id)
	{
		$config = $this->container->getParameter('sdf_billetterie');
		$em = $this->getDoctrine()->getManager();

		// ON VERIFIE QU'IL Y A BIEN UN NUM DE BILLET ASSOCIE

		$repoBillets = $em->getRepository('SDFBilletterieBundle:Billet');
		$repoKeys = $em->getRepository('SDFBilletterieBundle:Appkey');
		$repoCAS = $em->getRepository('SDFBilletterieBundle:CasUser');

		if (gettype($id) == 'NULL') return automatedJsonResponse(array("isValid" => 'noId'));

		// ON VERIFIE QU'IL Y A UN BILLET ASSOCIE

		$billet = $repoBillets->find($id);
		if (gettype($billet) == 'NULL') return automatedJsonResponse(array("isValid" => 'noBillet'));
		if($billet->getConsomme()) return automatedJsonResponse(array("isValid" => 'alreadyUsed'));
		if(!$billet->getValide()) return automatedJsonResponse(array("isValid" => 'notValid'));
		if (!isset($_GET['key']) || gettype($repoKeys->findOneBy(array('relationKey' => $_GET['key']))) == 'NULL')
			return automatedJsonResponse(array('isValid' => 'invalidKey'));

		$utilisateurConcerne = $billet->getUtilisateur();
		$userCAS = $repoCAS->findOneBy(array('user' => $utilisateurConcerne));

		if(gettype($userCAS) != 'NULL'){
			$ginger = json_decode(file_get_contents($config['ginger']['url'].$userCAS->getLoginCAS().'?key='.$config['ginger']['key']));
			try {
				$adulte = $ginger->is_adulte;
			} catch (Exception $e) {
				$adulte = true;
			}
		} else {
			$adulte = true;
		}

		$tabReponse = array(
			"isValid" => "ok",
			"nom" => $billet->getNom(),
			"prenom" => $billet->getPrenom(),
			"majeur" => $adulte
			);

		$this->instantLog($billet->getUtilisateur(),"Numéro associé au billet ".$billet->getId()." lu");

		return automatedJsonResponse($tabReponse);
	}

	public function getNFCAssociatedBilletsAction($id)
	{
		$config = $this->container->getParameter('sdf_billetterie');
		$em = $this->getDoctrine()->getManager();

		try {
			$gingerResult = json_decode(@file_get_contents($config['ginger']['url'] . 'badge/'. $id . '?key=' . $config['ginger']['key']));
			if(gettype($gingerResult) != 'NULL'){
				$loginAssocie = $gingerResult->login;
				$adulte = $gingerResult->is_adulte;
			} else {
				return automatedJsonResponse(array('isValide' => 'noLoginFound'));
			}
		} catch (ContextErrorException $e) {
			return automatedJsonResponse(array('isValide' => 'noLoginFound'));
		}


		$repoKeys = $em->getRepository('SDFBilletterieBundle:Appkey');
		if (!isset($_GET['key']) || gettype($repoKeys->findOneBy(array('relationKey' => $_GET['key']))) == 'NULL')
			return automatedJsonResponse(array('isValide' => 'invalidKey'));

		$repoUserCas = $em->getRepository('SDFBilletterieBundle:CasUser');
		$userCASConcerne = $repoUserCas->findOneBy(array('loginCAS' => $loginAssocie));

		if(gettype($userCASConcerne) == 'NULL') return automatedJsonResponse(array('isValide' => 'loginNotInDatabase'));

		$billetsAffiches = array('isValide' => 'yes','isAdulte' => $adulte);

		$billets = $em->getRepository('SDFBilletterieBundle:Billet')->findBy(array('utilisateur' => $userCASConcerne->getUser()));
		$i=0;
		foreach($billets as $billet){
				if ($billet->getValide() && !$billet->getConsomme()){
					$billetsAffiches[$i++] = array('id' => $billet->getId(),
					'nom' => $billet->getNom(),
					'prenom' => $billet->getPrenom());

					$this->instantLog($billet->getUtilisateur(),"Badge associé au billet ".$billet->getId()." lu");
				}
		}

		return automatedJsonResponse($billetsAffiches);
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

	public function validateBilletAction($id){

		$em = $this->getDoctrine()->getManager();
		$repoBillets = $em->getRepository('SDFBilletterieBundle:Billet');

		$repoKeys = $em->getRepository('SDFBilletterieBundle:Appkey');
		if (!isset($_GET['key']) || gettype($repoKeys->findOneBy(array('relationKey' => $_GET['key']))) == 'NULL')
			return automatedJsonResponse(array('validation' => 'invalidKey'));

		$billet = $repoBillets->find($id);

		if (gettype($billet) == 'NULL' || !$billet->getValide())
			return automatedJsonResponse(array('validation' => 'noBillet'));

		if ($billet->getConsomme())
			return automatedJsonResponse(array('validation' => 'alreadyConsumed'));

		$billet->setConsomme(true);

		$this->instantLog($billet->getUtilisateur(),"Billet ".$billet->getId()." consommé");
		$em->persist($billet);
		$em->flush();

		return automatedJsonResponse(array('validation' => 'ok'));

	}

	private function checkCASUserExists($login)
	{
		$em = $this->getDoctrine()->getManager();

		$repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:CasUser');

		$userActif = $repositoryUserCAS->findOneBy(array('username' => $login));

		if (gettype($userActif) == "NULL") {
			throw new UserNotFoundException();
		}
	}

	private function checkExtUserExists($login){
		$repositoryUserExt = $this
			->getDoctrine()
			->getManager()
			->getRepository('SDFBilletterieBundle:User')
			;
		$userActif = $repositoryUserExt->findOneBy(array('login' => $login));
		if(gettype($userActif) == "NULL") throw new UserNotFoundException();

	}

	private function checkUserExists()
	{
		// VERIFIE QU'IL Y A BIEN UN UTILISATEUR DE CONNECTE
		// SINON, JETTE UNE ERREUR NOTFOUNDUSEREXCEPTION
		return !is_null($this->getUser());
	}

	private function checkConsultationRights($userID, $billetID)
	{
		/* VERIFIE QUE L'UTILISATEUR ACTIF D'ID $userID A BIEN ACCES A CE BILLET $billetID
				SINON, JETTE UNE ERREUR DENIEDACCESSEXCEPTION
		*/
			$em=$this->getDoctrine()->getManager();
			$repoBillet = $em->getRepository('SDFBilletterieBundle:Billet');
			$repoUser = $em->getRepository('SDFBilletterieBundle:Utilisateur');
			$billet = $repoBillet->find($id);

			// on vérifie les accès
			if (gettype($billet) == 'NULL' || $billet->getUtilisateur()->getId() != $userRefActif->getId()){
					$utilisateur = $repoUser->find($userID);
					if (!$utilisateur->getAdmin()) throw AccessDeniedHttpException();
			}
	}

	private function checkUserIsAdmin(){
		// RETOURNE TRUE SI L'USER EST ADMIN
		// RETOURNE FALSE S'IL N'Y A PAS D'USER CONNECTÉ OU S'IL N'EST PAS ADMIN
		return in_array('ROLE_ADMIN', $this->getUser()->getRoles());
	}

	private function checkBilletAvailable($tarifID){
		/* RETURNS TRUE IF AVAILABLE

		RETURNS FALSE OTHERWISE */
		$user = $this->getUser();

		$em = $this->getDoctrine()->getManager();
		$repoTarifs = $em->getRepository('SDFBilletterieBundle:Tarif');
		$billetDispo = $repoTarifs->find($tarifID);

		// pour chaque tarif, on veut obtenir le nombre de billets dispos restant pour cette personne

		$nbBilletDeCeTarif = count($em
				->createQuery('SELECT b FROM SDFBilletterieBundle:Billet b JOIN b.tarif t WHERE b.user = :user AND t.id = :idTarif')
				->setParameter('user', $user)
				->setParameter('idTarif',$billetDispo->getId())
				->getResult());

		$qteRestante = $billetDispo->getQuantiteParPersonne() - $nbBilletDeCeTarif;

		// on veut ensuite obtenir le nombre de billets déjà achetés par tout le monde

		$query = $em
				->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t WHERE t.id = :idTarif')
				->setParameter('idTarif',$billetDispo->getId())
				->getResult();

		$qteRestanteGlobale = $billetDispo->getQuantite() - $query[0]['c'];

		// on veut enfin obtenir le nombre de billets achetés correspondant à l'évènement

		$query = $em
						->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t JOIN t.evenement e WHERE e.id = :idEvent')
						->setParameter('idEvent',$billetDispo->getEvenement()->getId())
						->getResult();

		$qteRestanteEvent = $billetDispo->getEvenement()->getQuantiteMax() - $query[0]['c'];

		// on veut également vérifier que le pot commun n'a pas été consommé
		if (gettype($billetDispo->getPotCommun()) != 'NULL') {
		$query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.user u JOIN b.tarif t JOIN t.potCommun p WHERE u.id = :id AND p.id = :idPot')
						->setParameter('id',$userRefActif->getId())
						->setParameter('idPot',$billetDispo->getPotCommun()->getId())
						->getResult();

		$potCommunNonConsomme = ($query[0]['c'] < 1); } else $potCommunNonConsomme = true;

		if ($qteRestante > 0
			&& $qteRestanteGlobale > 0
			&& $qteRestanteEvent > 0
			&& $potCommunNonConsomme)
			return true;
		else return false;
	}

	private function checkIfInvalidBillet($userID){

		$em = $this->getDoctrine()->getManager();
		/* RETURNS FALSE IF NO INVALID BILLET

		ELSE RETURNS THE ID */

		$query = $em->createQuery('SELECT b FROM SDFBilletterieBundle:Billet b JOIN b.user u WHERE b.user = :user AND b.valide = FALSE');
			$query->setParameter('user',$this->getUser());
			$resultatRequeteBilletsInvalides = $query->getResult();

		if (count($resultatRequeteBilletsInvalides) > 0){
			foreach($resultatRequeteBilletsInvalides as $billet){
				return $billet->getId();
			}
		} else return false;
	}
}
