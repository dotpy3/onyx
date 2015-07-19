<?php

namespace SDF\BilletterieBundle\Controller;

use Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Constraints\Date;

use Ginger\Client\GingerClient;
use Ginger\Client\ApiException;

use SDF\BilletterieBundle\Entity\Utilisateur;
use SDF\BilletterieBundle\Entity\UtilisateurExterieur;
use SDF\BilletterieBundle\Entity\UtilisateurCAS;
use SDF\BilletterieBundle\Entity\Log;
use SDF\BilletterieBundle\Utils\XmlParser\XmlParser;

class connexionController extends Controller
{
    protected function isUserStoredInDatabase($user)
    {
      return !gettype($user) == 'NULL';
    }

    public function recuperationLogin()
    {
      //retourne -1 si récupération impossible
      // sinon, lance une exception "Impossible de se connecter"

      $data = file_get_contents('https://cas.utc.fr/cas/serviceValidate?service=http://'.$_SERVER["HTTP_HOST"].$this->get('router')->generate('sdf_billetterie_connexionCAS').'&ticket='.$_GET['ticket']);
      if (empty($data)) throw new Exception("Impossible de se connecter : Impossible d'obtenir un ticket du CAS");
      $parsed = new XmlParser($data);
      if (!isset($parsed->array['cas:serviceResponse']['cas:authenticationSuccess']['cas:user'])) throw new Exception("Impossible de se connecter : Impossible d'obtenir le login du retour CAS");
      //return $parsed->array['cas:serviceResponse']['cas:authenticationSuccess']['cas:user'];
      return $parsed;
    }

    public function connexionCASAction()
    {
      $config = $this->container->getParameter('sdf_billetterie');
      $em = $this->getDoctrine()->getManager();

      if (session_id() != '' && isset($_SESSION['typeUser'])
        && $_SESSION['typeUser'] == 'cas'
        && isset($_SESSION['user'])
        && $em->getRepository('SDFBilletterieBundle:UtilisateurCAS')->findOneBy(array('loginCAS' => $_SESSION['user']))
        )
        return $this->redirect($this->get('router')->generate('sdf_billetterie_indexBilletterie',array('message'=>'affichage')));

      if(!isset($_GET['ticket'])) return $this->redirect('https://cas.utc.fr/cas/login?service=http://'.$_SERVER["HTTP_HOST"].$this->get('router')->generate('sdf_billetterie_connexionCAS'));
        // pas de ticket reçu : pas de connexion CAS initialisée : on redirige vers la page CAS

        // SINON : ticket reçu : connexion initialisée, il faut récupérer le token
      try {
        $retourCAS = $this->recuperationLogin();
      } catch (Exception $e) {
        return $this->generateUrl('sdf_billetterie_homepage_connexionError');
      }
        $login=$retourCAS->array['cas:serviceResponse']['cas:authenticationSuccess']['cas:user'];
        $repositoryUser = $this
          ->getDoctrine()
          ->getManager()
          ->getRepository('SDFBilletterieBundle:Utilisateur')
        ;

        $repositoryUserCAS = $this
          ->getDoctrine()
          ->getManager()
          ->getRepository('SDFBilletterieBundle:UtilisateurCAS')
        ;

        $UserDeCeLogin = $repositoryUserCAS->findOneBy(array('loginCAS' => $login));
        if (!$this->isUserStoredInDatabase($UserDeCeLogin)){
          // l'user n'est pas dans la BDD

          $ginger = new GingerClient($config['ginger']['key'], $config['ginger']['url']);
          try {
            $resultatGinger = $ginger->getUser($login);
          }
          catch (ApiException $e){
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage_connexionError'));
          } catch (Exception $e){
            return $this->redirect($this->generateUrl('sdf_billetterie_homepage_connexionError'));
          }

          $casUser = new UtilisateurCAS();
          $user = new Utilisateur();
          $casUser->setLoginCAS($login);
          $casUser->setUser($user);
          $casUser->setUserBadge($resultatGinger->badge_uid);
          $casUser->setCotisant($resultatGinger->is_cotisant);

          $user->setNom($resultatGinger->nom);
          $user->setPrenom($resultatGinger->prenom);
          $user->setEmail($resultatGinger->mail);
          $user->setAdmin(false);
          $user->setBirthday(new \DateTime('00-00-0000'));


          $em->persist($casUser);
          $em->flush();


          $log1 = new Log();
          $log1->setUser($user);
          $log1->setContent("Création du compte de ".$casUser->getLoginCAS());
          $log1->setDate(new \DateTime());

          $em->persist($log1);
          $em->flush();
        }

        if(session_id() == '') {
          session_start();
        }
        $_SESSION['typeUser'] = 'cas';
        $_SESSION['user'] = $login;

        return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message' => 'affichage')));

        //$casUser->set
       // on récupère le ticket CAS
       // on obtient le login dans $login

      // on vérifie si le login existe dans la base des logins

      // sinon on le crée

      // après cette étape, on initialise la session

      // puis on le redirige vers la liste des billets
    }

    public function creationCompteAction(){
      return $this->render('SDFBilletterieBundle:Default:inscription.html.twig', array(
        'inscriptionError' => false,
        'mdpProblem' => false,
        'birthdayProblem' => false,
        'loginExists' => false
      ));
    }

    function verifBirthday($dateBirthday){
      $arrayBirthday = explode('-',$dateBirthday);
      if (count($arrayBirthday) != 3) throw new Exception("Pas 3 nombres");
    }

    function verifLoginNonExistant($loginTente){
      $repositoryUserExt = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
      ;
      $UserDeCeLogin = $repositoryUserExt->findOneBy(array('login' => $loginTente));
      if ($this->isUserStoredInDatabase($UserDeCeLogin)) throw new Exception("Utilisateur déjà existant");
    }

    public function verifFormulaireAction(){
      if (!isset($_POST['password']) || !isset($_POST['confirmation']) || !isset($_POST['username']) || !isset($_POST['prenom']) || !isset($_POST['nom']) || !isset($_POST['birthday']) || !isset($_POST['mail']) || $_POST['username'] == ''|| $_POST['nom'] == ''|| $_POST['prenom'] == ''|| $_POST['birthday'] == ''|| $_POST['mail'] == ''){
        return $this->render('SDFBilletterieBundle:Default:inscription.html.twig', array('inscriptionError' => true, 'mdpProblem' => false, 'birthdayProblem' => false, 'loginExists' => false));
      }

      if ($_POST['password'] == '' || $_POST['password'] != $_POST['confirmation']){
        return $this->render('SDFBilletterieBundle:Default:inscription.html.twig', array('inscriptionError' => false, 'mdpProblem' => true, 'birthdayProblem' => false, 'loginExists' => false));
      }
      $dateinvalide= false; $logininvalide = false;
      $arrayBirthday = explode('-',$_POST['birthday']);
      if (count($arrayBirthday) != 3) $dateinvalide = true;
      if ($dateinvalide) return $this->render('SDFBilletterieBundle:Default:inscription.html.twig', array('inscriptionError' => false, 'mdpProblem' => false, 'birthdayProblem' => true, 'loginExists' => false));
      if (!checkdate($arrayBirthday[1],$arrayBirthday[0],$arrayBirthday[2])) $dateinvalide = true;
      if ($dateinvalide) return $this->render('SDFBilletterieBundle:Default:inscription.html.twig', array('inscriptionError' => false, 'mdpProblem' => false, 'birthdayProblem' => true, 'loginExists' => false));


      $repositoryUserExt = $this
      ->getDoctrine()
      ->getManager()
      ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
      ;

      $UserDeCeLogin = $repositoryUserExt->findOneBy(array('login' => $_POST['username']));
      if ($this->isUserStoredInDatabase($UserDeCeLogin)) $logininvalide = true;
      if ($logininvalide) return $this->render('SDFBilletterieBundle:Default:inscription.html.twig', array('inscriptionError' => false, 'mdpProblem' => false, 'birthdayProblem' => false, 'loginExists' => true));


      $extUser = new UtilisateurExterieur();
      $user = new Utilisateur();

      $user->setNom($_POST['nom']);
      $user->setPrenom($_POST['prenom']);
      $user->setEmail($_POST['mail']);
      $user->setBirthday(new \DateTime($_POST['birthday']));
      $user->setAdmin(false);

      $extUser->setMotDePasse(hash('sha512',$_POST['password']));
      $extUser->setLogin($_POST["username"]);
      $extUser->setUser($user);

      $this->getDoctrine()->getManager()->persist($extUser);
      $this->getDoctrine()->getManager()->flush();

      return $this->render('SDFBilletterieBundle:Default:index.html.twig', array('connexionError' => false, 'inscriptionReussie' => true,'accesExterieur' => false));
    }

    public function connexionExtAction(){
      if(!isset($_POST['username']) || !isset($_POST['password']) || $_POST['username'] == '' || $_POST['password'] == ''){
        return $this->redirect($this->generateUrl('sdf_billetterie_homepage_connexionError'));
      }
      $repositoryUserExt = $this
      ->getDoctrine()
      ->getManager()
      ->getRepository('SDFBilletterieBundle:UtilisateurExterieur')
      ;

      $UserDeCeLogin = $repositoryUserExt->findOneBy(array('login' => $_POST['username']));
      if (!$this->isUserStoredInDatabase($UserDeCeLogin)) return $this->render('SDFBilletterieBundle:Default:index.html.twig', array('connexionError' => true, 'inscriptionReussie' => false,'accesExterieur' => false));
      if($UserDeCeLogin->getMotDePasse() != hash('sha512',$_POST['password'])) return $this->render('SDFBilletterieBundle:Default:index.html.twig', array('connexionError' => true, 'inscriptionReussie' => false,'accesExterieur' => false));

      // à ce point s'il n'y a pas eu redirection, alors le login est validé

      if(session_id() == '') {
        session_start();
      }
      $_SESSION['typeUser'] = 'exterieur';
      $_SESSION['user'] = $UserDeCeLogin->getLogin();

      return $this->redirect($this->generateUrl('sdf_billetterie_indexBilletterie',array('message'=>'affichage')));
    }

    public function adminAction(){
      return $this->render('SDFBilletterieBundle:Default:admin.html.twig');
    }

    public function decoAction() {
      if(session_id() != '') {
        session_destroy();
      }
      return $this->redirect('https://cas.utc.fr/cas/logout');
    }
}
