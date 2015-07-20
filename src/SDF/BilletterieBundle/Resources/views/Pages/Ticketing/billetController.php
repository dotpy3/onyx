<?php

namespace SDF\BilletterieBundle\Controller;

use SDF\BilletterieBundle\Entity\Evenement;
use SDF\BilletterieBundle\Entity\Tarif;
use SDF\BilletterieBundle\Entity\Navette;
use SDF\BilletterieBundle\Entity\Billet;
use SDF\BilletterieBundle\Entity\Trajet;
use SDF\BilletterieBundle\Entity\Utilisateur;
use SDF\BilletterieBundle\Entity\Contraintes;
use SDF\BilletterieBundle\Entity\Log;
use SDF\BilletterieBundle\Entity\UtilisateurExterieur;
use SDF\BilletterieBundle\Entity\UtilisateurCAS;
use SDF\BilletterieBundle\Entity\PotCommunTarifs;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\HttpFoundation\Request;
use SDF\BilletterieBundle\Form\TarifType;
use SDF\BilletterieBundle\Form\TrajetType;
use SDF\BilletterieBundle\Form\NavetteType;
use SDF\BilletterieBundle\Form\BilletType;

use \Payutc\Client\AutoJsonClient;
use \Payutc\Client\JsonException;

function checkConnexion(){
	if(isset($_SESSION['typeUser'])){
		return TRUE;
	} else {
		return FALSE;
	}
}
/*
$doctrine = $this->getDoctrine();
$em = $this->getManager();
$userRepository = $this->getRepository('SDFBilletterieBundle:User');

*/

class PDF extends \fpdf\FPDF {
    function EAN13($x, $y, $barcode, $h=16, $w=.35)
    {
       $this->Barcode($x, $y, $barcode, $h, $w, 13);
    }

    function UPC_A($x, $y, $barcode, $h=16, $w=.35)
    {
       $this->Barcode($x, $y, $barcode, $h, $w, 12);
    }

    function GetCheckDigit($barcode)
    {
       //Compute the check digit
       $sum=0;
       for($i=1;$i<=11;$i+=2)
           $sum+=3*$barcode{$i};
       for($i=0;$i<=10;$i+=2)
           $sum+=$barcode{$i};
       $r=$sum%10;
       if($r>0)
           $r=10-$r;
       return $r;
    }

    function TestCheckDigit($barcode)
    {
       //Test validity of check digit
       $sum=0;
       for($i=1;$i<=11;$i+=2)
           $sum+=3*$barcode{$i};
       for($i=0;$i<=10;$i+=2)
           $sum+=$barcode{$i};
       return ($sum+$barcode{12})%10==0;
    }

    function Barcode($x, $y, $barcode, $h, $w, $len)
    {
       //Padding
       $barcode=str_pad($barcode, $len-1, '0', STR_PAD_LEFT);
       if($len==12)
           $barcode='0'.$barcode;
       //Add or control the check digit
       if(strlen($barcode)==12)
           $barcode.=$this->GetCheckDigit($barcode);
       elseif(!$this->TestCheckDigit($barcode))
           $this->Error('Incorrect check digit');
       //Convert digits to bars
       $codes=array(
           'A'=>array(
               '0'=>'0001101', '1'=>'0011001', '2'=>'0010011', '3'=>'0111101', '4'=>'0100011',
               '5'=>'0110001', '6'=>'0101111', '7'=>'0111011', '8'=>'0110111', '9'=>'0001011'),
           'B'=>array(
               '0'=>'0100111', '1'=>'0110011', '2'=>'0011011', '3'=>'0100001', '4'=>'0011101',
               '5'=>'0111001', '6'=>'0000101', '7'=>'0010001', '8'=>'0001001', '9'=>'0010111'),
           'C'=>array(
               '0'=>'1110010', '1'=>'1100110', '2'=>'1101100', '3'=>'1000010', '4'=>'1011100',
               '5'=>'1001110', '6'=>'1010000', '7'=>'1000100', '8'=>'1001000', '9'=>'1110100')
           );
       $parities=array(
           '0'=>array('A', 'A', 'A', 'A', 'A', 'A'),
           '1'=>array('A', 'A', 'B', 'A', 'B', 'B'),
           '2'=>array('A', 'A', 'B', 'B', 'A', 'B'),
           '3'=>array('A', 'A', 'B', 'B', 'B', 'A'),
           '4'=>array('A', 'B', 'A', 'A', 'B', 'B'),
           '5'=>array('A', 'B', 'B', 'A', 'A', 'B'),
           '6'=>array('A', 'B', 'B', 'B', 'A', 'A'),
           '7'=>array('A', 'B', 'A', 'B', 'A', 'B'),
           '8'=>array('A', 'B', 'A', 'B', 'B', 'A'),
           '9'=>array('A', 'B', 'B', 'A', 'B', 'A')
           );
       $code='101';
       $p=$parities[$barcode{0}];
       for($i=1;$i<=6;$i++)
           $code.=$codes[$p[$i-1]][$barcode{$i}];
       $code.='01010';
       for($i=7;$i<=12;$i++)
           $code.=$codes['C'][$barcode{$i}];
       $code.='101';
       //Draw bars
       for($i=0;$i<strlen($code);$i++)
       {
           if($code{$i}=='1')
               $this->Rect($x+$i*$w, $y, $w, $h, 'F');
       }
       //Print text uder barcode
       $this->SetFont('arial', '', 12);
       $this->Text($x, $y+$h+11/$this->k, substr($barcode, -$len));
    }
    
    function Rotate($angle,$x=-1,$y=-1) { 

        if($x==-1) 
            $x=$this->x; 
        if($y==-1) 
            $y=$this->y; 
        if($this->angle!=0) 
            $this->_out('Q'); 
        $this->angle=$angle; 
        if($angle!=0) 

        { 
            $angle*=M_PI/180; 
            $c=cos($angle); 
            $s=sin($angle); 
            $cx=$x*$this->k; 
            $cy=($this->h-$y)*$this->k; 
             
            $this->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy)); 
        } 
    } 
}

class billetController extends Controller
{
    public function listeBilletsAction($message = false)
    {

        $em = $this->getDoctrine()->getManager();
        //if ($_SESSION['typeUser'] == 'exterieur') return new Response("Connexion réussie pour l'utilisateur " . $_SESSION['user']->getLogin());
    	// verifier que l'utilisateur est bien connecté
    	//if (!checkConnexion($_SESSION)) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        if(session_id() == '') {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }
        if($_SESSION['typeUser'] == 'exterieur'){
            // UTILISATEUR EXTERIEUR
            $repositoryUserExt = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
              ;
            $userActif = $repositoryUserExt->findOneBy(array('login' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
        } elseif ($_SESSION['typeUser'] == 'cas') {
            // UTILISATEUR CAS
            //$userActif = $_SESSION['user']->getUser();

            $repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:UtilisateurCAS');
            $userActif = $repositoryUserCAS->findOneBy(array('loginCAS' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();

        } else {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }
        $nomUserActif = $userRefActif->getPrenom() . ' ' . $userRefActif->getNom();

        $em = $this->getDoctrine()->getManager();

        /*
        ON VEUT COMMENCER PAR RECUPERER LES BILLETS ACHETES
        */

        $listeBilletsAchetes = Array();
        $repoBillets = $em->getRepository('SDFBilletterieBundle:Billet');

        $query = $em->createQuery('SELECT b FROM SDFBilletterieBundle:Billet b JOIN b.utilisateur u WHERE u.id = :id AND b.valide = TRUE');
        $query->setParameter('id',$userRefActif->getId());
        $resultatRequeteBilletsAchetes = $query->getResult();

        $i = 0;

        foreach($resultatRequeteBilletsAchetes as $billetAchete){
            if(gettype($billetAchete->getNavette()) != 'NULL') {
                $listeBilletsAchetes[$i] = Array(
                    'nom' => $billetAchete->getPrenom() . ' ' . $billetAchete->getNom(),
                    'type' => $billetAchete->getTarif()->getNomTarif(),
                    'navette' => true,
                    'horaireNavette' => $billetAchete->getNavette()->getHoraireDepartFormat(),
                    'departNavette' => $billetAchete->getNavette()->getTrajet()->getLieuDepart(),
                    'id' => $billetAchete->getId()
                    );
            } else {
                $listeBilletsAchetes[$i] = Array(
                    'nom' => $billetAchete->getPrenom() . ' ' . $billetAchete->getNom(),
                    'type' => $billetAchete->getTarif()->getNomTarif(),
                    'navette' => false,
                    'id' => $billetAchete->getId()
                    );
            }
            $i++;
        }
        $test = "";
        foreach($resultatRequeteBilletsAchetes as $billetAchete) $test .= $billetAchete->getPrenom();
        /*

        ON VEUT ENSUITE RECUPERER LES BILLETS DISPONIBLES

        */

        $listeBilletsDispos = Array();
        $repoTarifs = $em->getRepository('SDFBilletterieBundle:Tarif');

        if($_SESSION['typeUser'] == 'cas' && $userActif->getCotisant()) $accesTarifsCotisants ='';
        else $accesTarifsCotisants = ' c.doitEtreCotisant = FALSE AND';

        if($_SESSION['typeUser'] == 'cas' && !($userActif->getCotisant())) $accesTarifsNonCotisant = '';
        else $accesTarifsNonCotisant = ' c.doitNePasEtreCotisant = FALSE AND';

        if($_SESSION['typeUser'] == 'exterieur') $necessaireExterieur = ' c.accessibleExterieur = TRUE AND';
        else $necessaireExterieur = '';



        $query = $em->createQuery('SELECT p.id AS idPot FROM SDFBilletterieBundle:Billet b JOIN b.tarif t JOIN t.potCommun p JOIN b.utilisateur u WHERE u.id = :id AND b.valide = TRUE AND t.potCommun IS NOT NULL');
        $query->setParameter('id',$userRefActif->getId());
        $resultatRequetePotsCommunsUtilises = $query->getResult();

        $requetePotsCommuns = "";

        foreach($resultatRequetePotsCommunsUtilises as $potCommun){
            $requetePotsCommuns .= (" AND NOT p.id = " . $potCommun['idPot']);
        }

        $query = $em->createQuery('SELECT t FROM SDFBilletterieBundle:Tarif t JOIN t.contraintes c JOIN t.evenement e
            WHERE c.doitEtreCotisant = :doitEtreCoti AND c.doitNePasEtreCotisant = :doitpasetrecoti' . $necessaireExterieur . '
            AND c.debutMiseEnVente < :dateAJD AND c.finMiseEnVente > :dateAJD');
        /*return new Response('SELECT t FROM SDFBilletterieBundle:Tarif t JOIN t.contraintes c JOIN t.evenement e
            WHERE c.doitEtreCotisant = :doitEtreCoti AND c.doitNePasEtreCotisant = :doitpasetrecoti' . $necessaireExterieur . '
            AND c.debutMiseEnVente < :dateAJD AND c.finMiseEnVente > :dateAJD');*/
        $query = $em->createQuery('SELECT t FROM SDFBilletterieBundle:Tarif t JOIN t.contraintes c JOIN t.evenement e LEFT JOIN t.potCommun p WHERE
            ' . $accesTarifsCotisants . $accesTarifsNonCotisant . $necessaireExterieur . ' c.debutMiseEnVente < :dateAJD AND c.finMiseEnVente > :dateAJD' . $requetePotsCommuns);
        $dateString = date_format(date_create(),'Y-m-d H:i:s');
        $query->setParameter('dateAJD',$dateString);
        /*->setParameter('dateAJD',date_create())
        ->setParameter('doitpasetrecoti',$accesTarifsNonCotisant);*/
        $resultatRequeteBilletsDispos = $query->getResult();

        $i = 0;

        foreach($resultatRequeteBilletsDispos as $billetDispo){

            // pour chaque tarif, on veut obtenir le nombre de billets dispos restant pour cette personne

            $nbBilletDeCeTarif = count($em->createQuery('SELECT b FROM SDFBilletterieBundle:Billet b JOIN b.utilisateur u JOIN b.tarif t WHERE u.id = :id AND t.id = :idTarif')
                ->setParameter('id',$userRefActif->getId())
                ->setParameter('idTarif',$billetDispo->getId())
                ->getResult());

            $qteRestante = $billetDispo->getQuantiteParPersonne() - $nbBilletDeCeTarif;

            // on veut ensuite obtenir le nombre de billets déjà achetés par tout le monde

            $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t WHERE t.id = :idTarif')
                ->setParameter('idTarif',$billetDispo->getId())
                ->getResult();

            $qteRestanteGlobale = $billetDispo->getQuantite() - $query[0]['c'];

            // on veut enfin obtenir le nombre de billets achetés correspondant à l'évènement

            $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t JOIN t.evenement e WHERE e.id = :idEvent')
                    ->setParameter('idEvent',$billetDispo->getEvenement()->getId())
                    ->getResult();

            $qteRestanteEvent = $billetDispo->getEvenement()->getQuantiteMax() - $query[0]['c'];

            // on veut également vérifier que le pot commun n'a pas été consommé
            if (gettype($billetDispo->getPotCommun()) != 'NULL') {
            $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.utilisateur u JOIN b.tarif t JOIN t.potCommun p WHERE u.id = :id AND p.id = :idPot')
                    ->setParameter('id',$userRefActif->getId())
                    ->setParameter('idPot',$billetDispo->getPotCommun()->getId())
                    ->getResult();

            $potCommunNonConsomme = ($query[0]['c'] < 1); } else $potCommunNonConsomme = true;

            if ($qteRestante > 0 && $qteRestanteGlobale > 0 && $qteRestanteEvent > 0 && $potCommunNonConsomme){

                $listeBilletsDispos[$i] = Array(
                    'nom' => $billetDispo->getNomTarif(),
                    'prix' => $billetDispo->getPrix(),
                    'quantiteRestante' => min($qteRestante,$qteRestanteGlobale,$qteRestanteEvent),
                    'id' => $billetDispo->getId()
                    );
                $i++;
            }
        }

        $query = $em->createQuery('SELECT b FROM SDFBilletterieBundle:Billet b JOIN b.utilisateur u WHERE u.id = :id AND b.valide = FALSE');
        $query->setParameter('id',$userRefActif->getId());
        $resultatRequeteBilletsInvalides = $query->getResult();

        /*

        ON AGREGE ENSUITE TOUT CELA DANS LA VUE

        */

        if (count($listeBilletsAchetes) == 0) $listeBilletsAchetes = 0;
        if (count($listeBilletsDispos) == 0) $listeBilletsDispos = 0;

        /* ON RECUPERE LES BILLETS NON VALIDÉS */

        if (count($resultatRequeteBilletsInvalides) > 0){
            foreach($resultatRequeteBilletsInvalides as $premierBilletInvalide){
                return $this->render('SDFBilletterieBundle:billet:listebillets.html.twig', array('message' => $message, 'billetInvalide' => true, 'billetNonValide' => $premierBilletInvalide->getId(), 'billetsAchetes' => $listeBilletsAchetes, 'billetsDispos' => $listeBilletsDispos, 'nomUtilisateur' => $nomUserActif));
            }
        }


        return $this->render('SDFBilletterieBundle:billet:listebillets.html.twig', array('message' => $message, 'billetInvalide' => false, 'billetNonValide' => false, 'billetsAchetes' => $listeBilletsAchetes, 'billetsDispos' => $listeBilletsDispos, 'nomUtilisateur' => $nomUserActif));

    	// verifier qu'il est bien dans la liste des utilisateurs
    	// verifier les billets associés

    	// vérifier les billets disponibles
    }

    public function changeParamBilletAction($id)
    {
    	// vérifier que l'user est connecté
    	// vérifier qu'il est bien dans la liste des users
    	// vérifier qu'il y ait bien un billet $id associé à l'user

    	// modifier les paramètres du billet

    	// retourner la liste des billets avec la notification

    	return $this->render('SDFBilletterieBundle:Default:index.html.twig', array('connexionError' => false));
    }

    public function checkContraintesAction(Request $request){

        // ON VERIFIE QUE L'UTILISATEUR EST ADMIN
        if (!isset($_SESSION['typeUser']) || !isset($_SESSION['user'])) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        if($_SESSION['typeUser'] == 'exterieur'){
            // UTILISATEUR EXTERIEUR
            $repositoryUserExt = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
              ;
            $userActif = $repositoryUserExt->findOneBy(array('login' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
            if (!($userRefActif->getAdmin())) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        } elseif ($_SESSION['typeUser'] == 'cas') {
            // UTILISATEUR CAS
            //$userActif = $_SESSION['user']->getUser();
            $em=$this->getDoctrine()->getManager();
            $repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:UtilisateurCAS');
            $userActif = $repositoryUserCAS->findOneBy(array('loginCAS' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
            if (!($userRefActif->getAdmin())) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        } else {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }



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

            return $this->render('SDFBilletterieBundle:billet:add.html.twig', array(
          'form' => $form->createView(),'name' => "set de contraintes", 'addError' => false, 'addOK' => true
        ));
        }


        return $this->render('SDFBilletterieBundle:billet:add.html.twig', array(
          'form' => $form->createView(),'name' => "set de contraintes", 'addError' => false, 'addOK' => false
        ));
    }

    public function checkEventAction(Request $request){

        // ON VERIFIE QUE L'UTILISATEUR EST ADMIN
        if (!isset($_SESSION['typeUser']) || !isset($_SESSION['user'])) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        if($_SESSION['typeUser'] == 'exterieur'){
            // UTILISATEUR EXTERIEUR
            $repositoryUserExt = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
              ;
            $userActif = $repositoryUserExt->findOneBy(array('login' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
            if (!($userRefActif->getAdmin())) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        } elseif ($_SESSION['typeUser'] == 'cas') {
            // UTILISATEUR CAS
            //$userActif = $_SESSION['user']->getUser();
            $em=$this->getDoctrine()->getManager();
            $repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:UtilisateurCAS');
            $userActif = $repositoryUserCAS->findOneBy(array('loginCAS' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
            if (!($userRefActif->getAdmin())) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        } else {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }

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

            return $this->render('SDFBilletterieBundle:billet:add.html.twig', array(
          'form' => $form->createView(),'name' => "évènement", 'addError' => false, 'addOK' => true
        ));
        }


        return $this->render('SDFBilletterieBundle:billet:add.html.twig', array(
          'form' => $form->createView(),'name' => "évènement", 'addError' => false, 'addOK' => false
        ));
    }

    public function tarifsAction(Request $request){

        // ON VERIFIE QUE L'UTILISATEUR EST ADMIN
        if (!isset($_SESSION['typeUser']) || !isset($_SESSION['user'])) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        if($_SESSION['typeUser'] == 'exterieur'){
            // UTILISATEUR EXTERIEUR
            $repositoryUserExt = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
              ;
            $userActif = $repositoryUserExt->findOneBy(array('login' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
            if (!($userRefActif->getAdmin())) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        } elseif ($_SESSION['typeUser'] == 'cas') {
            // UTILISATEUR CAS
            //$userActif = $_SESSION['user']->getUser();
            $em=$this->getDoctrine()->getManager();
            $repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:UtilisateurCAS');
            $userActif = $repositoryUserCAS->findOneBy(array('loginCAS' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
            if (!($userRefActif->getAdmin())) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        } else {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }



        $tarif = new Tarif();

        $form = $this->get('form.factory')->create(new TarifType, $tarif);

        if($form->handleRequest($request)->isValid()){
            $em = $this->getDoctrine()->getManager();
            $em->persist($tarif);
            $em->flush();

            return $this->render('SDFBilletterieBundle:billet:add.html.twig', array(
          'form' => $form->createView(),'name' => "tarif", 'addError' => false, 'addOK' => true
        ));
        
        }

        return $this->render('SDFBilletterieBundle:billet:add.html.twig', array(
          'form' => $form->createView(),'name' => "tarif", 'addError' => false, 'addOK' => false
        ));

    }

    public function checkTrajetsNavetteAction(Request $request){

        // ON VERIFIE QUE L'UTILISATEUR EST ADMIN
        if (!isset($_SESSION['typeUser']) || !isset($_SESSION['user'])) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        if($_SESSION['typeUser'] == 'exterieur'){
            // UTILISATEUR EXTERIEUR
            $repositoryUserExt = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
              ;
            $userActif = $repositoryUserExt->findOneBy(array('login' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
            if (!($userRefActif->getAdmin())) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        } elseif ($_SESSION['typeUser'] == 'cas') {
            // UTILISATEUR CAS
            //$userActif = $_SESSION['user']->getUser();
            $em=$this->getDoctrine()->getManager();
            $repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:UtilisateurCAS');
            $userActif = $repositoryUserCAS->findOneBy(array('loginCAS' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
            if (!($userRefActif->getAdmin())) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        } else {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }



        $tarif = new Trajet();

        $form = $this->get('form.factory')->create(new TrajetType, $tarif);

        if($form->handleRequest($request)->isValid()){
            $em = $this->getDoctrine()->getManager();
            $em->persist($tarif);
            $em->flush();

            return $this->render('SDFBilletterieBundle:billet:add.html.twig', array(
          'form' => $form->createView(),'name' => "trajet", 'addError' => false, 'addOK' => true
        ));
        
        }

        return $this->render('SDFBilletterieBundle:billet:add.html.twig', array(
          'form' => $form->createView(),'name' => "trajet", 'addError' => false, 'addOK' => false
        ));
    }

    public function checkNavettesAction(Request $request){

        // ON VERIFIE QUE L'UTILISATEUR EST ADMIN
        if (!isset($_SESSION['typeUser']) || !isset($_SESSION['user'])) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        if($_SESSION['typeUser'] == 'exterieur'){
            // UTILISATEUR EXTERIEUR
            $repositoryUserExt = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
              ;
            $userActif = $repositoryUserExt->findOneBy(array('login' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
            if (!($userRefActif->getAdmin())) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        } elseif ($_SESSION['typeUser'] == 'cas') {
            // UTILISATEUR CAS
            //$userActif = $_SESSION['user']->getUser();
            $em=$this->getDoctrine()->getManager();
            $repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:UtilisateurCAS');
            $userActif = $repositoryUserCAS->findOneBy(array('loginCAS' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
            if (!($userRefActif->getAdmin())) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        } else {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }


        $tarif = new Navette();

        $form = $this->get('form.factory')->create(new NavetteType, $tarif);

        if($form->handleRequest($request)->isValid()){
            $em = $this->getDoctrine()->getManager();
            $em->persist($tarif);
            $em->flush();

            return $this->render('SDFBilletterieBundle:billet:add.html.twig', array(
          'form' => $form->createView(),'name' => "navette", 'addError' => false, 'addOK' => true
        ));
        
        }

        return $this->render('SDFBilletterieBundle:billet:add.html.twig', array(
          'form' => $form->createView(),'name' => "navette", 'addError' => false, 'addOK' => false
        ));
    }

    public function billetAdminAction(Request $request){

        // ON VERIFIE QUE L'UTILISATEUR EST ADMIN
        if (!isset($_SESSION['typeUser']) || !isset($_SESSION['user'])) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        if($_SESSION['typeUser'] == 'exterieur'){
            // UTILISATEUR EXTERIEUR
            $repositoryUserExt = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
              ;
            $userActif = $repositoryUserExt->findOneBy(array('login' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
            if (!($userRefActif->getAdmin())) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        } elseif ($_SESSION['typeUser'] == 'cas') {
            // UTILISATEUR CAS
            //$userActif = $_SESSION['user']->getUser();
            $em=$this->getDoctrine()->getManager();
            $repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:UtilisateurCAS');
            $userActif = $repositoryUserCAS->findOneBy(array('loginCAS' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
            if (!($userRefActif->getAdmin())) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        } else {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }



        $tarif = new Billet();

        $form = $this->get('form.factory')->create(new BilletType, $tarif);

        if($form->handleRequest($request)->isValid()){
            $em = $this->getDoctrine()->getManager();
            $em->persist($tarif);
            $em->flush();

            return $this->render('SDFBilletterieBundle:billet:add.html.twig', array(
          'form' => $form->createView(),'name' => "billet", 'addError' => false, 'addOK' => true
        ));
        
        }

        return $this->render('SDFBilletterieBundle:billet:add.html.twig', array(
          'form' => $form->createView(),'name' => "billet", 'addError' => false, 'addOK' => false
        ));
    }

    public function paramBilletAction($id){

        /*

        ON COMMENCE PAR VÉRIFIER LA CONNEXION

        */

        $em = $this->getDoctrine()->getManager();
        //if ($_SESSION['typeUser'] == 'exterieur') return new Response("Connexion réussie pour l'utilisateur " . $_SESSION['user']->getLogin());
        // verifier que l'utilisateur est bien connecté
        //if (!checkConnexion($_SESSION)) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        if(session_id() == '') {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }
        if($_SESSION['typeUser'] == 'exterieur'){
            // UTILISATEUR EXTERIEUR
            $repositoryUserExt = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
              ;
            $userActif = $repositoryUserExt->findOneBy(array('login' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
        } elseif ($_SESSION['typeUser'] == 'cas') {
            // UTILISATEUR CAS
            //$userActif = $_SESSION['user']->getUser();

            $repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:UtilisateurCAS');
            $userActif = $repositoryUserCAS->findOneBy(array('loginCAS' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();

        } else {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }

        /* LA CONNEXION EST VÉRIFIÉE

        ON VÉRIFIE LES ACCÈS AU BILLET */


        $em=$this->getDoctrine()->getManager();
        $repoBillet = $em->getRepository('SDFBilletterieBundle:Billet');
        $repoNavettes = $em->getRepository('SDFBilletterieBundle:Navette');
        $billet = $repoBillet->find($id);

        // on vérifie les accès
        if (gettype($billet) == 'NULL' || $billet->getUtilisateur()->getId() != $userRefActif->getId()){
            return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'accessBillet')));
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
        $tabNavettes = Array(); $i = 0;
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
            $tabNavettes[$i] = $enregNavette;
            $i++;
        }


        $log1 = new Log();
        $log1->setUser($userRefActif);
        $log1->setContent("User accède aux paramètres du billet ".$billet->getId());
        $log1->setDate(new \DateTime());

        $em->persist($log1);
        $em->flush();

        return $this->render('SDFBilletterieBundle:billet:paramBillet.html.twig', array(
          'billetID' => $billet->getId(),
          'typeBillet' => $typeBillet,
          'nomBillet' => $nomBillet,
          'prenomBillet' => $prenomSurLeBillet,
          'noNavetteSelected' => $noNavetteSelected,
          'navettes' => $tabNavettes
        ));

        return new Response ('Vous avez bien les droits pour accéder à ce billet');
    }

    public function changedParamBilletAction($id){

        /*

        ON COMMENCE PAR VÉRIFIER LA CONNEXION

        */

        $em = $this->getDoctrine()->getManager();
        //if ($_SESSION['typeUser'] == 'exterieur') return new Response("Connexion réussie pour l'utilisateur " . $_SESSION['user']->getLogin());
        // verifier que l'utilisateur est bien connecté
        //if (!checkConnexion($_SESSION)) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        if(session_id() == '') {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }
        if($_SESSION['typeUser'] == 'exterieur'){
            // UTILISATEUR EXTERIEUR
            $repositoryUserExt = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
              ;
            $userActif = $repositoryUserExt->findOneBy(array('login' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
        } elseif ($_SESSION['typeUser'] == 'cas') {
            // UTILISATEUR CAS
            //$userActif = $_SESSION['user']->getUser();

            $repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:UtilisateurCAS');
            $userActif = $repositoryUserCAS->findOneBy(array('loginCAS' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();

        } else {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }

        /* LA CONNEXION EST VÉRIFIÉE

        ON VÉRIFIE LES ACCÈS AU BILLET */


        $em=$this->getDoctrine()->getManager();
        $repoBillet = $em->getRepository('SDFBilletterieBundle:Billet');
        $repoNavettes = $em->getRepository('SDFBilletterieBundle:Navette');
        $billet = $repoBillet->find($id);

        // on vérifie les accès
        if (gettype($billet) == 'NULL' || $billet->getUtilisateur()->getId() != $userRefActif->getId()){
            return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'accessBillet')));
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



        $log1 = new Log();
        $log1->setUser($userRefActif);
        $log1->setContent("User change les paramètres du billet ". $billet->getId());
        $log1->setDate(new \DateTime());

        $em->persist($log1);
        $em->flush();

        return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'savingOptionsSuccess')));

        return new Response("Droits vérifiés LOL");

    }

    public function accessBilletAction($id){

        /*

        ON COMMENCE PAR VÉRIFIER LA CONNEXION

        */

        $em = $this->getDoctrine()->getManager();
        //if ($_SESSION['typeUser'] == 'exterieur') return new Response("Connexion réussie pour l'utilisateur " . $_SESSION['user']->getLogin());
        // verifier que l'utilisateur est bien connecté
        //if (!checkConnexion($_SESSION)) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        if(session_id() == '') {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }
        if($_SESSION['typeUser'] == 'exterieur'){
            // UTILISATEUR EXTERIEUR
            $repositoryUserExt = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
              ;
            $userActif = $repositoryUserExt->findOneBy(array('login' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
        } elseif ($_SESSION['typeUser'] == 'cas') {
            // UTILISATEUR CAS
            //$userActif = $_SESSION['user']->getUser();

            $repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:UtilisateurCAS');
            $userActif = $repositoryUserCAS->findOneBy(array('loginCAS' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();

        } else {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }

        /* LA CONNEXION EST VÉRIFIÉE

        ON VÉRIFIE LES ACCÈS AU BILLET */


        $em=$this->getDoctrine()->getManager();
        $repoBillet = $em->getRepository('SDFBilletterieBundle:Billet');
        $repoNavettes = $em->getRepository('SDFBilletterieBundle:Navette');
        $billet = $repoBillet->find($id);

        // on vérifie les accès
        if (gettype($billet) == 'NULL' || $billet->getUtilisateur()->getId() != $userRefActif->getId()){
            return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'accessBillet')));
        }

        /* ACCÈS AU BILLET VÉRIFIÉ
    
        ON FAIT MAINTENANT L'ACTION VOULUE */

        $pdf = new PDF();

        $pdf->Open();
        $pdf->AddPage('L');
        $pdf->SetAutoPageBreak(true,'5');
        $adresseRawBillet = __DIR__ . '/../Resources/images/rawBillet.jpg';
        $pdf->Image($adresseRawBillet,0,0,297,210);
        $pdf->SetFont('arial','B','20');
        $pdf->SetTextColor(0,0,0);
        $pdf->SetXY(174,13+11);
        $pdf->Write(10,iconv("UTF-8", "ISO-8859-1",ucfirst(strtolower($billet->getPrenom()))));
        $pdf->SetXY(174,21+11);
        $pdf->Write(10,iconv("UTF-8", "ISO-8859-1",strtoupper($billet->getNom())));
        $pdf->SetFont('arial','','20');
        $pdf->SetXY(174,42+11);
        $pdf->Write(10,iconv("UTF-8", "ISO-8859-1",strtoupper($billet->getTarif()->getNomTarif())));

        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('arial','','11');
        $pdf->SetXY(174,6+11);
        $pdf->Write(10,"Num".chr(233)."ro de billet : ".$billet->getId());

        $pdf->SetXY(174,32+11);
        //$pdf->SetFont('times','','30');
        $pdf->SetTextColor(0,0,0);
        $pdf->Write(10, "Prix TTC : " . $billet->getTarif()->getPrix() . ' '.chr(128));

        $pdf->SetXY(174,58+11);
        $pdf->Write(10,"Billet achet".chr(233)." par : ".iconv("UTF-8", "ISO-8859-1", strtoupper($billet->getUtilisateur()->getNom())." ".ucfirst($billet->getUtilisateur()->getPrenom())));
        $pdf->SetTextColor(0,0,0);
        $pdf->EAN13(174, 72+11, $billet->getBarcode(), 12, 1);

        return $pdf->Output('','I');



        $log1 = new Log();
        $log1->setUser($userRefActif);
        $log1->setContent("Billet ".$billet->getId()." généré pour l'utilisateur");
        $log1->setDate(new \DateTime());

        $em->persist($log1);
        $em->flush();


        return new Response("Répertoire : " . __DIR__);
    }

    public function buyBilletAction($typeBillet){
        /*

        ON COMMENCE PAR VÉRIFIER LA CONNEXION

        */

        $id = $typeBillet;

        $em = $this->getDoctrine()->getManager();
        //if ($_SESSION['typeUser'] == 'exterieur') return new Response("Connexion réussie pour l'utilisateur " . $_SESSION['user']->getLogin());
        // verifier que l'utilisateur est bien connecté
        //if (!checkConnexion($_SESSION)) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        if(session_id() == '') {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }
        if($_SESSION['typeUser'] == 'exterieur'){
            // UTILISATEUR EXTERIEUR
            $repositoryUserExt = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
              ;
            $userActif = $repositoryUserExt->findOneBy(array('login' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
        } elseif ($_SESSION['typeUser'] == 'cas') {
            // UTILISATEUR CAS
            //$userActif = $_SESSION['user']->getUser();

            $repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:UtilisateurCAS');
            $userActif = $repositoryUserCAS->findOneBy(array('loginCAS' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();

        } else {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }

        /* LA CONNEXION EST VÉRIFIÉE

        ON VÉRIFIE LES ACCÈS AU BILLET */

        $billetDispo = $em->getRepository('SDFBilletterieBundle:Tarif')->find($id);

        if(gettype($billetDispo) == 'NULL') return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));

        $nbBilletDeCeTarif = count($em->createQuery('SELECT b FROM SDFBilletterieBundle:Billet b JOIN b.utilisateur u JOIN b.tarif t WHERE u.id = :id AND t.id = :idTarif')
                ->setParameter('id',$userRefActif->getId())
                ->setParameter('idTarif',$id)
                ->getResult());

        $qteRestante = $billetDispo->getQuantiteParPersonne() - $nbBilletDeCeTarif;

            // on veut ensuite obtenir le nombre de billets déjà achetés par tout le monde

        $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t WHERE t.id = :idTarif')
                ->setParameter('idTarif',$id)
                ->getResult();

        $qteRestanteGlobale = $billetDispo->getQuantite() - $query[0]['c'];

            // on veut enfin obtenir le nombre de billets achetés correspondant à l'évènement

            $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t JOIN t.evenement e WHERE e.id = :idEvent')
                    ->setParameter('idEvent',$billetDispo->getEvenement()->getId())
                    ->getResult();

            $qteRestanteEvent = $billetDispo->getEvenement()->getQuantiteMax() - $query[0]['c'];

            // on veut également vérifier que le pot commun n'a pas été consommé
            if (gettype($billetDispo->getPotCommun()) != 'NULL') {
            $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.utilisateur u JOIN b.tarif t JOIN t.potCommun p WHERE u.id = :id AND p.id = :idPot')
                    ->setParameter('id',$userRefActif->getId())
                    ->setParameter('idPot',$billetDispo->getPotCommun()->getId())
                    ->getResult();

            $potCommunNonConsomme = ($query[0]['c'] < 1); } else $potCommunNonConsomme = true;

            if ($qteRestante < 0 || $qteRestanteGlobale < 0 || $qteRestanteEvent < 0 || !($potCommunNonConsomme))
            {
                return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));
            }

        /*

            ACCES AU BILLET VERIFIE

        */

        $repoNavettes = $em->getRepository('SDFBilletterieBundle:Navette');

        $arrayToutesNavettes = $repoNavettes->findAll();
        $tabNavettes = Array(); $i = 0;
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
            $tabNavettes[$i] = $enregNavette;
            $i++;
        } 

        return $this->render('SDFBilletterieBundle:billet:achatbillet.html.twig', array(
          'billetID' => $billetDispo->getId(),
          'typeBillet' => $billetDispo->getNomTarif(),
          'prixBillet' => $billetDispo->getPrix(),
          'navettes' => $tabNavettes
        ));



        $log1 = new Log();
        $log1->setUser($userRefActif);
        $log1->setContent("User accède à l'achat du billet " . $billetDispo->getId());
        $log1->setDate(new \DateTime());

        $em->persist($log1);
        $em->flush();

        return new Response("On peut bien acheter le billet");
    }

    public function payUTCcallbackAction($token){
        /*

        ON COMMENCE PAR VÉRIFIER LA CONNEXION

        */

        $id = $token;

        $em = $this->getDoctrine()->getManager();
        //if ($_SESSION['typeUser'] == 'exterieur') return new Response("Connexion réussie pour l'utilisateur " . $_SESSION['user']->getLogin());
        // verifier que l'utilisateur est bien connecté
        //if (!checkConnexion($_SESSION)) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        if(session_id() == '') {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }
        if($_SESSION['typeUser'] == 'exterieur'){
            // UTILISATEUR EXTERIEUR
            $repositoryUserExt = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
              ;
            $userActif = $repositoryUserExt->findOneBy(array('login' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
        } elseif ($_SESSION['typeUser'] == 'cas') {
            // UTILISATEUR CAS
            //$userActif = $_SESSION['user']->getUser();

            $repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:UtilisateurCAS');
            $userActif = $repositoryUserCAS->findOneBy(array('loginCAS' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();

        } else {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }

        /* LA CONNEXION EST VÉRIFIÉE

        ON VÉRIFIE LES ACCÈS AU BILLET */

        $billetDispo = $em->getRepository('SDFBilletterieBundle:Tarif')->find($id);

        if(gettype($billetDispo) == 'NULL') return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));

        $nbBilletDeCeTarif = count($em->createQuery('SELECT b FROM SDFBilletterieBundle:Billet b JOIN b.utilisateur u JOIN b.tarif t WHERE u.id = :id AND t.id = :idTarif')
                ->setParameter('id',$userRefActif->getId())
                ->setParameter('idTarif',$id)
                ->getResult());

        $qteRestante = $billetDispo->getQuantiteParPersonne() - $nbBilletDeCeTarif;

            // on veut ensuite obtenir le nombre de billets déjà achetés par tout le monde

        $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t WHERE t.id = :idTarif')
                ->setParameter('idTarif',$id)
                ->getResult();

        $qteRestanteGlobale = $billetDispo->getQuantite() - $query[0]['c'];

            // on veut enfin obtenir le nombre de billets achetés correspondant à l'évènement

            $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.tarif t JOIN t.evenement e WHERE e.id = :idEvent')
                    ->setParameter('idEvent',$billetDispo->getEvenement()->getId())
                    ->getResult();

            $qteRestanteEvent = $billetDispo->getEvenement()->getQuantiteMax() - $query[0]['c'];

            // on veut également vérifier que le pot commun n'a pas été consommé
            if (gettype($billetDispo->getPotCommun()) != 'NULL') {
            $query = $em->createQuery('SELECT COUNT(b) AS c FROM SDFBilletterieBundle:Billet b JOIN b.utilisateur u JOIN b.tarif t JOIN t.potCommun p WHERE u.id = :id AND p.id = :idPot')
                    ->setParameter('id',$userRefActif->getId())
                    ->setParameter('idPot',$billetDispo->getPotCommun()->getId())
                    ->getResult();

            $potCommunNonConsomme = ($query[0]['c'] < 1); } else $potCommunNonConsomme = true;

            if ($qteRestante < 0 || $qteRestanteGlobale < 0 || $qteRestanteEvent < 0 || !($potCommunNonConsomme))
            {
                return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));
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

            $log1 = new Log();
            $log1->setUser($userRefActif);
            $log1->setContent("Billet ".$billetCree->getId()." généré dans la BDD pour l'user ");
            $log1->setDate(new \DateTime());

            $em->persist($log1);
            $em->flush();

            try {
                // CONNEXION A PAYUTC
                $payutcClient = new AutoJsonClient("https://api.nemopay.net/services/", "WEBSALE", array(CURLOPT_PROXY => 'proxyweb.utc.fr:3128', CURLOPT_TIMEOUT => 5), "Payutc Json PHP Client", array(), "payutc", "e3a4936545d321b2567d977bcc325667");
                
                $arrayItems = array(array($billetDispo->getIdPayutc()));
                $item = json_encode($arrayItems);
                //return new Response($item);
                $billetIds = array();
                $billetIds[] = $billetCree->getTarif()->getIdPayutc();
                $returnURL = 'http://' . $_SERVER["HTTP_HOST"].$this->get('router')->generate('sdf_billetterie_routingPostPaiement',array('id'=>$billetCree->getId()));
                $callback_url = 'http://' . $_SERVER["HTTP_HOST"].$this->get('router')->generate('sdf_billetterie_callbackDePAYUTC',array('id'=>$billetCree->getId()));
                //return new Response($item);
                $c = $payutcClient->apiCall('createTransaction',
                    array("fun_id" => 10,
                        "items" => $item,
                        "return_url" => $returnURL,
                        "callback_url" => $callback_url,
                        "mail" => $userRefActif->getEmail()
                        ));

                $billetCree->setIdPayutc($c->tra_id);

                $em->persist($billetCree);
                $em->flush();



                $log1 = new Log();
                $log1->setUser($userRefActif);
                $log1->setContent("Connexion réussie à Payutc dans le cadre de l'achat du billet ".$billetCree->getId()." associé à l'identifiant Payutc ".$c->tra_id);
                $log1->setDate(new \DateTime());

                $em->persist($log1);
                $em->flush();

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

            return new Response('billet crée FDP');

            return new Response ("Le billet peut être créé");
    }

    public function callbackFromPayutcAction($id){
        $em = $this->getDoctrine()->getManager();
        $repoBillets = $em->getRepository('SDFBilletterieBundle:Billet');
        $billet = $repoBillets->find($id);
        if (gettype($billet) == 'NULL') return new Response("échec d'obtention du billet");
        //if ($billet->getValide() == true) return new Response("déjà validé");

        // CONNEXION A PAYUTC
        $payutcClient = new AutoJsonClient("https://api.nemopay.net/services/", "WEBSALE", array(CURLOPT_PROXY => 'proxyweb.utc.fr:3128', CURLOPT_TIMEOUT => 5), "Payutc Json PHP Client", array(), "payutc", "e3a4936545d321b2567d977bcc325667");
        $data = $payutcClient->apiCall('getTransactionInfo',
            array('fun_id' => 10,
                'tra_id' => $billet->getIdPayutc()
                )
            );

        if ($data->status == "V"){
            $billet->setValide(true);
            $em->persist($billet);

            $log1 = new Log();
            $log1->setUser();
            $log1->setContent("Billet ".$billet->getId()." validé par Payutc : payé ");
            $log1->setDate(new \DateTime());

            $em->persist($log1);
            $em->flush();

            $message = \Swift_Message::newInstance()->setSubject('Votre billet pour la Soirée des Finaux 2015')
                ->setFrom('soireedesfinaux@assos.utc.fr')
                ->setTo($billet->getUtilisateur()->getEmail())
                ->setBody($this->renderView('SDFBilletterieBundle:billet:mailenvoi.html.twig'),'text/html');

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

    public function routingPostPayAction($id){
        $em = $this->getDoctrine()->getManager();
        $repoBillets = $em->getRepository('SDFBilletterieBundle:Billet');

        $billet = $repoBillets->find($id);

        if(gettype($billet) == 'NULL') return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));

        if($billet->getValide()) return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'billetSuccess')));

        else return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'achatBilletError')));
    }

    public function relancerTransactionAction($id){
        $em = $this->getDoctrine()->getManager();
        $repoBillets = $em->getRepository('SDFBilletterieBundle:Billet');

        $billet = $repoBillets->find($id);

        if (gettype($billet) == 'NULL' || $billet->getValide() == true) {
            return new Response('Billet inexistant ou déjà validé !');
        }

        return $this->redirect('http://payutc.nemopay.net/validation?tra_id='.$billet->getIdPayutc());
    }

    public function annulerBilletInvalideAction($id){

        /*

        ON COMMENCE PAR VÉRIFIER LA CONNEXION

        */

        $em = $this->getDoctrine()->getManager();
        //if ($_SESSION['typeUser'] == 'exterieur') return new Response("Connexion réussie pour l'utilisateur " . $_SESSION['user']->getLogin());
        // verifier que l'utilisateur est bien connecté
        //if (!checkConnexion($_SESSION)) return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        if(session_id() == '') {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }
        if($_SESSION['typeUser'] == 'exterieur'){
            // UTILISATEUR EXTERIEUR
            $repositoryUserExt = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
              ;
            $userActif = $repositoryUserExt->findOneBy(array('login' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();
        } elseif ($_SESSION['typeUser'] == 'cas') {
            // UTILISATEUR CAS
            //$userActif = $_SESSION['user']->getUser();

            $repositoryUserCAS = $em->getRepository('SDFBilletterieBundle:UtilisateurCAS');
            $userActif = $repositoryUserCAS->findOneBy(array('loginCAS' => $_SESSION['user']));
            if(gettype($userActif) == "NULL") return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
            $userRefActif = $userActif->getUser();

        } else {
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
        }

        /* LA CONNEXION EST VÉRIFIÉE

        ON VÉRIFIE LES ACCÈS AU BILLET */


        $em=$this->getDoctrine()->getManager();
        $repoBillet = $em->getRepository('SDFBilletterieBundle:Billet');
        $repoNavettes = $em->getRepository('SDFBilletterieBundle:Navette');
        $billet = $repoBillet->find($id);

        // on vérifie les accès
        if (gettype($billet) == 'NULL' || $billet->getUtilisateur()->getId() != $userRefActif->getId()){
            return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'accessBillet')));
        }

        /* ACCÈS AU BILLET VÉRIFIÉ
    
        ON FAIT MAINTENANT L'ACTION VOULUE */

        if($billet->getValide() == true) return new Response('Le billet a été validé !');

        $em->remove($billet);

        return new Response('Le billet a bien été annulé !');
    }
}
