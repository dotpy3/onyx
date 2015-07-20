<?php

namespace SDF\BilletterieBundle\Controller;

use Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Constraints\Date;

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
use SDF\BilletterieBundle\Form\PotCommunTarifsType;
use SDF\BilletterieBundle\Exception\UserNotFoundException;
use SDF\BilletterieBundle\Utils\Pdf\Pdf;

use \Payutc\Client\AutoJsonClient;
use \Payutc\Client\JsonException;

class TicketingController extends Controller
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

	private function instantLog($user,$content)
	{
		$em = $this->getDoctrine()->getManager();

		$log = new Log();
		$log->setInstantLogAs($user,$content);

		$em->persist($log);
		$em->flush();
	}

	public function listeBilletsAction($message = false)
	{
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$remainingPlacesByPrice = array();

		// Find all the tickets bought by the user (and validated by PayUtc)
		$boughtTickets = $em->getRepository('SDFBilletterieBundle:Billet')->findAllValidTicketsForUser($user);

		// Find all the available prices for the user [Without stock availability management]
		$availablePrices = $em->getRepository('SDFBilletterieBundle:Tarif')->findAllAvailablePricesForUser($user);

		// Then handle stocks availability
		$availablePrices = array_filter($availablePrices, function ($price) use ($user, $em, &$remainingPlacesByPrice) {
			// Assume that the price is available by default
			$isPriceAvailable = false;

			$remainingPlaces = $this->countRemainingPlacesForPrice($price);

			if ($remainingPlaces > 0) {
				$isPriceAvailable = true;
				$remainingPlacesByPrice[$price->getId()] = $remainingPlaces;
			}

			return $isPriceAvailable;
		});

		/* ON RECUPERE LES BILLETS NON VALIDÉS */
		$unvalidTicket = $em->getRepository('SDFBilletterieBundle:Billet')->findOneUnvalidTicketsForUser($user);

		return $this->render('SDFBilletterieBundle:Pages/Ticketing:list.html.twig', array(
			'message' => $message,
			'boughtTickets' => $boughtTickets,
			'unvalidTicket' => $unvalidTicket,
			'availablePrices' => $availablePrices,
			'remainingPlacesByPrice' => $remainingPlacesByPrice,
		));
	}

	protected function countRemainingPlacesForPrice($price)
	{
		$remainingPlaces = 0;
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

		return $remainingPlaces;
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

	private function checkUserIsAdmin(){
		// RETOURNE TRUE SI L'USER EST ADMIN
		// RETOURNE FALSE S'IL N'Y A PAS D'USER CONNECTÉ OU S'IL N'EST PAS ADMIN
			try {
				$this->checkUserExists();
			} catch (UserNotFoundException $e) {
				return false;
			}
			if ($_SESSION['usertype'] == 'cas') $userActif = $em->getRepository('SDFBilletterieBundle:CasUser')
						->findOneBy(array('loginCAS' => $login));
			else $userActif = $em->getRepository('SDFBilletterieBundle:User')
						->findOneBy(array('login' => $login));
			$userRefActif = $userActif->getUser();
			if (!($userRefActif->getAdmin())) return false;
			else return true;
	}

	public function checkContraintesAction(Request $request){

			// ON VERIFIE QUE L'UTILISATEUR EXISTE & EST ADMIN
			if (!checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));


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
			if (!checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));

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
			if (!checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));

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
			if (!checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));

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
			if (!checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));

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
			if (!checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));

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
			if (!checkUserIsAdmin()) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));

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

	public function paramBilletAction($id){

			/*        ON COMMENCE PAR VÉRIFIER LA CONNEXION        */

			try {
				$this->checkUserExists();
				if ($_SESSION['usertype'] == 'cas') $userActif = $em->getRepository('SDFBilletterieBundle:CasUser')
							->findOneBy(array('loginCAS' => $login));
				else $userActif = $em->getRepository('SDFBilletterieBundle:User')
							->findOneBy(array('login' => $login));
				$userRefActif = $userActif->getUser();
				/* LA CONNEXION EST VÉRIFIÉE        ON VÉRIFIE LES ACCÈS AU BILLET */
				$this->checkConsultationRights($userRefActif->getId(),$id);
			} catch (UserNotFoundException $e) {
				return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
			} catch (AccessDeniedHttpException $e) {
				return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',
					array('message'=>'accessBillet')));
			}

			/* LISTE DES VARIABLES TWIG :

			- billetID
			- typeBillet
			- nomBillet
			- prenomSurLeBillet
			- noNavetteSelected
			- navettes, composées d'arrays avec :
					- idNavette
					- desactivee
					- navetteSelectionnee
					- lieuDepart
					- horaireNavette
					- placesRestantes

			*/

			$typeBillet = $billet->getTarif()->getNomTarif();
			$nomBillet = $billet->getNom();
			if (gettype($billet->getNavette()) == 'NULL') $noNavetteSelected = true;
			else $noNavetteSelected = false;
			$prenomSurLeBillet = $billet->getPrenom();

			$arrayToutesNavettes = $repoNavettes->findAll();
			$tabNavettes = Array();
			foreach($arrayToutesNavettes as $navetteEtudiee){
					$enregNavette = Array();
					$enregNavette['idNavette'] = $navetteEtudiee->getId();
					$enregNavette['lieuDepart'] = $navetteEtudiee->getTrajet()->getLieuDepart();
					$enregNavette['horaireNavette'] = $navetteEtudiee->getHoraireDepartFormat();
					if(!$noNavetteSelected && ($billet->getNavette()->getId() == $navetteEtudiee->getId())){
							$enregNavette['navetteSelectionnee'] = true;
					} else {
							$enregNavette['navetteSelectionnee'] = false;
					}
					$requete = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.navette n WHERE n.id = :idNavette')
							->setParameter('idNavette',$navetteEtudiee->getId())
							->getResult();
					if ($requete[0]['c'] < $navetteEtudiee->getCapaciteMax()){
							$enregNavette['desactivee'] = false;
					} else {
							$enregNavette['desactivee'] = true;
					}
					if (gettype($billet->getNavette()) != 'NULL' &&$navetteEtudiee->getId() == $billet->getNavette()->getId()) $enregNavette['desactivee'] = false;
					$enregNavette['placesRestantes'] = - ($requete[0]['c'] - $navetteEtudiee->getCapaciteMax());
					$tabNavettes[] = $enregNavette;
			}

			return $this->render('SDFBilletterieBundle:Pages/Ticketing:paramBillet.html.twig', array(
				'billetID' => $billet->getId(),
				'typeBillet' => $typeBillet,
				'nomBillet' => $nomBillet,
				'prenomBillet' => $prenomSurLeBillet,
				'noNavetteSelected' => $noNavetteSelected,
				'navettes' => $tabNavettes
			));
	}

	public function changedParamBilletAction($id){

			/*

			ON COMMENCE PAR VÉRIFIER LA CONNEXION

			*/

			try {
				$this->checkUserExists();
				if ($_SESSION['usertype'] == 'cas') $userActif = $em->getRepository('SDFBilletterieBundle:CasUser')
							->findOneBy(array('loginCAS' => $login));
				else $userActif = $em->getRepository('SDFBilletterieBundle:User')
							->findOneBy(array('login' => $login));
				$userRefActif = $userActif->getUser();
				/* LA CONNEXION EST VÉRIFIÉE        ON VÉRIFIE LES ACCÈS AU BILLET */
				$this->checkConsultationRights($userRefActif->getId(),$id);
			} catch (UserNotFoundException $e) {
				return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
			} catch (AccessDeniedHttpException $e) {
				return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',
					array('message'=>'accessBillet')));
			}

			/* ACCÈS AU BILLET VÉRIFIÉ

			ON FAIT MAINTENANT L'ACTION VOULUE */

			if ($_POST['nom'] != '') $billet->setNom($_POST['nom']);
			if ($_POST['prenom'] != '') $billet->setPrenom($_POST['prenom']);

			if ($_POST['sel1'] == 'noNavette') {
					//il faut mettre la navette à null pour le billet étudié

					$qb = $em->createQueryBuilder();
					$qb->update('SDFBilletterieBundle:Billet','billet');

					$qb->set('billet.navette',':navettenull');
					$qb->setParameter('navettenull',null);

					$qb->where('billet.id = :id');
					$qb->setParameter('id',$id);

					$qb->getQuery()->execute();
			}
			else {
					if (gettype($repoNavettes->find($_POST['sel1'])) != 'NULL'){
							// navette valide, on vérifie qu'il y a assez de place
							$requete = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.navette n WHERE n.id = :idNavette')
							->setParameter('idNavette',$_POST['sel1'])
							->getResult();
							if ($requete[0]['c'] < $repoNavettes->find($_POST['sel1']) || $billet->getNavette()->getId() == $_POST['sel1']){
									// PLACE VALIDE
									// ON ATTRIBUE LA PLACE DANS LA NAVETTE
									$billet->setNavette($repoNavettes->find($_POST['sel1']));
							} else {
									return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'savingOptionsError')));
							}
					} else {
							return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'savingOptionsError')));
					}
			}

			$em->persist($billet);
			$em->flush();

			return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'savingOptionsSuccess')));

	}

	private function pdfGeneration($userPrenom,$userNom,$nomTarif,$billetID,
		$tarifPrix,$billetNom,$billetPrenom,$billetBarcode){

			$pdf = new Pdf();

			$pdf->Open();
			$pdf->AddPage('L');
			$pdf->SetAutoPageBreak(true,'5');
			$adresseRawBillet = __DIR__ . '/../Resources/images/rawBillet.jpg';
			$pdf->Image($adresseRawBillet,0,0,297,210);
			$pdf->SetFont('arial','B','20');
			$pdf->SetTextColor(0,0,0);
			$pdf->SetXY(174,13+11);
			$pdf->Write(10,iconv("UTF-8", "ISO-8859-1",ucfirst(strtolower($billetPrenom))));
			$pdf->SetXY(174,21+11);
			$pdf->Write(10,iconv("UTF-8", "ISO-8859-1",strtoupper($billetNom)));
			$pdf->SetFont('arial','','20');
			$pdf->SetXY(174,42+11);
			$pdf->Write(10,iconv("UTF-8", "ISO-8859-1",strtoupper($nomTarif)));

			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('arial','','11');
			$pdf->SetXY(174,6+11);
			$pdf->Write(10,"Num".chr(233)."ro de billet : ".$billetID);

			$pdf->SetXY(174,32+11);
			//$pdf->SetFont('times','','30');
			$pdf->SetTextColor(0,0,0);
			$pdf->Write(10, "Prix TTC : " . $tarifPrix . ' '.chr(128));

			$pdf->SetXY(174,58+11);
			$pdf->Write(10,"Billet achet".chr(233)." par : ".iconv("UTF-8", "ISO-8859-1", strtoupper($userNom)." ".ucfirst($userPrenom)));
			$pdf->SetTextColor(0,0,0);
			$pdf->EAN13(174, 72+11, $billetBarcode, 12, 1);

			return $pdf->Output('','I');

	}

	public function accessBilletAction($id){

			/*

			ON COMMENCE PAR VÉRIFIER LA CONNEXION

			*/

			try {
				$this->checkUserExists();
				if ($_SESSION['usertype'] == 'cas') $userActif = $em->getRepository('SDFBilletterieBundle:CasUser')
							->findOneBy(array('loginCAS' => $login));
				else $userActif = $em->getRepository('SDFBilletterieBundle:User')
							->findOneBy(array('login' => $login));
				$userRefActif = $userActif->getUser();
				/* LA CONNEXION EST VÉRIFIÉE        ON VÉRIFIE LES ACCÈS AU BILLET */
				$this->checkConsultationRights($userRefActif->getId(),$id);
			} catch (UserNotFoundException $e) {
				return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
			} catch (AccessDeniedHttpException $e) {
				return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',
					array('message'=>'accessBillet')));
			}

			/* ACCÈS AU BILLET VÉRIFIÉ

			ON FAIT MAINTENANT L'ACTION VOULUE */

			$pdf = pdfGeneration($billet->getUtilisateur()->getPrenom(),
				$billet->getUtilisateur()->getNom(),
				$billet->getTarif()->getNomTarif(),
				$billet->getId(),
				$billet->getTarif()->getPrix(),
				$billet->getNom(),
				$billet->getPrenom(),
				$billet->getBarcode());

			$reponse = new Response();
			$reponse->headers->set('Content-Type', 'application/pdf');

			$reponse->setContent($pdf);

			return $reponse;
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

	public function relancerTransactionAction($id){

			$dsn = 'mysql:dbname='.$this->PDOdatabase.';host='.$this->PDOhost;
			$tempUser = $this->user;
			$password = $this->password;

			try {
					$bdd = new \PDO($dsn, $tempUser, $password);

			} catch (\PDOException $e) {
					echo 'Connexion échouée : ' . $e->getMessage();
					exit();
			}

			$requete = $bdd->query('SELECT * FROM Billet WHERE id = '.$id);
			$resultat = $requete->fetch();
			if($resultat == false) return new Response('Billet introuvable');
			if($resultat['valide']) return new Response('Billet déjà validé !');
			return $this->redirect('http://payutc.nemopay.net/validation?tra_id='.$resultat['idPayutc']);
	}

	public function annulerBilletInvalideAction($id){

			try {
				$this->checkUserExists();
				if ($_SESSION['usertype'] == 'cas') $userActif = $em->getRepository('SDFBilletterieBundle:CasUser')
							->findOneBy(array('loginCAS' => $login));
				else $userActif = $em->getRepository('SDFBilletterieBundle:User')
							->findOneBy(array('login' => $login));
				$userRefActif = $userActif->getUser();
				/* LA CONNEXION EST VÉRIFIÉE        ON VÉRIFIE LES ACCÈS AU BILLET */
				$this->checkConsultationRights($userRefActif->getId(),$id);
			} catch (UserNotFoundException $e) {
				return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
			} catch (AccessDeniedHttpException $e) {
				return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',
					array('message'=>'accessBillet')));
			}

			/* ACCÈS AU BILLET VÉRIFIÉ

			ON FAIT MAINTENANT L'ACTION VOULUE */

			if($billet->getValide() == true) return new Response('Le billet a été validé !');

			$em->remove($billet);
			$em->flush();

			return new Response('Le billet a bien été annulé !');
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

		$em = $this->getDoctrine()->getManager();
			//if ($_SESSION['usertype'] == 'exterieur') return new Response("Connexion réussie pour l'utilisateur " . $_SESSION['username']->getLogin());
		// verifier que l'utilisateur est bien connecté
		//if (!checkConnexion($_SESSION)) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
			if(session_id() == '') throw new UserNotFoundException();
			if(!isset($_SESSION['username'])) throw new UserNotFoundException();
			if($_SESSION['usertype'] == 'exterieur'){
					$this->checkExtUserExists($_SESSION['username']);
					// UTILISATEUR EXTERIEUR


			} elseif ($_SESSION['usertype'] == 'cas') {
					$this->checkCASUserExists($_SESSION['username']);
					// UTILISATEUR CAS


			} else {
				throw new UserNotFoundException();
			}

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
}
