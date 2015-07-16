<?php

namespace SDF\BilletterieBundle\Controller;

use SDF\BilletterieBundle\Entity\Utilisateur;
use SDF\BilletterieBundle\Entity\UtilisateurExterieur;
use SDF\BilletterieBundle\Entity\UtilisateurCAS;
use SDF\BilletterieBundle\Entity\Log;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Date;

// xmlToArrayParser & CAS thx to github.com/robhub
class xmlToArrayParser { 
  /** The array created by the parser can be assigned to any variable: $anyVarArr = $domObj->array.*/ 
  public  $array = array(); 
  public  $parse_error = false; 
  private $parser; 
  private $pointer; 
  
  /** Constructor: $domObj = new xmlToArrayParser($xml); */ 
  public function __construct($xml) { 
    $this->pointer =& $this->array; 
    $this->parser = xml_parser_create("UTF-8"); 
    xml_set_object($this->parser, $this); 
    xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false); 
    xml_set_element_handler($this->parser, "tag_open", "tag_close"); 
    xml_set_character_data_handler($this->parser, "cdata"); 
    $this->parse_error = xml_parse($this->parser, ltrim($xml))? false : true; 
  } 
  
  /** Free the parser. */ 
  public function __destruct() { xml_parser_free($this->parser);} 
  /** Get the xml error if an an error in the xml file occured during parsing. */ 
  public function get_xml_error() { 
    if($this->parse_error) { 
      $errCode = xml_get_error_code ($this->parser); 
      $thisError =  "Error Code [". $errCode ."] \"<strong style='color:red;'>" . xml_error_string($errCode)."</strong>\", 
                            at char ".xml_get_current_column_number($this->parser) . " 
                            on line ".xml_get_current_line_number($this->parser).""; 
    }else $thisError = $this->parse_error; 
    return $thisError; 
  } 
  
  private function tag_open($parser, $tag, $attributes) { 
    $this->convert_to_array($tag, 'attrib'); 
    $idx=$this->convert_to_array($tag, 'cdata'); 
    if(isset($idx)) { 
      $this->pointer[$tag][$idx] = Array('@idx' => $idx,'@parent' => &$this->pointer); 
      $this->pointer =& $this->pointer[$tag][$idx]; 
    }else { 
      $this->pointer[$tag] = Array('@parent' => &$this->pointer); 
      $this->pointer =& $this->pointer[$tag]; 
    } 
    if (!empty($attributes)) { $this->pointer['attrib'] = $attributes; } 
  } 
  /** Adds the current elements content to the current pointer[cdata] array. */ 
  private function cdata($parser, $cdata) { $this->pointer['cdata'] = trim($cdata); } 
  private function tag_close($parser, $tag) { 
    $current = & $this->pointer; 
    if(isset($this->pointer['@idx'])) {unset($current['@idx']);} 
    
    $this->pointer = & $this->pointer['@parent']; 
    unset($current['@parent']); 
    
    if(isset($current['cdata']) && count($current) == 1) { $current = $current['cdata'];} 
    else if(empty($current['cdata'])) {unset($current['cdata']);} 
  } 
  
  /** Converts a single element item into array(element[0]) if a second element of the same name is encountered. */ 
  private function convert_to_array($tag, $item) { 
    if(isset($this->pointer[$tag][$item])) { 
      $content = $this->pointer[$tag]; 
      $this->pointer[$tag] = array((0) => $content); 
      $idx = 1; 
    }else if (isset($this->pointer[$tag])) { 
      $idx = count($this->pointer[$tag]); 
      if(!isset($this->pointer[$tag][0])) { 
        foreach ($this->pointer[$tag] as $key => $value) { 
            unset($this->pointer[$tag][$key]); 
            $this->pointer[$tag][0][$key] = $value; 
    }}}else $idx = null; 
    return $idx; 
  } 
}

class CAS
{
	const URL = 'https://cas.utc.fr/cas/';
	public static function authenticate()
	{
		if (!isset($_GET['ticket']) || empty($_GET['ticket'])) return -1;
		$data = file_get_contents(self::URL.'serviceValidate?service=http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'&ticket='.$_GET['ticket']);
		if (empty($data)) return -1;
		$parsed = new xmlToArrayParser($data);
		if (!isset($parsed->array['cas:serviceResponse']['cas:authenticationSuccess']['cas:user'])) return -1;
		return $parsed->array['cas:serviceResponse']['cas:authenticationSuccess']['cas:user'];
	}
	public static function login()
	{
		header('Location: '.self::URL.'login?service=http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	} 
	public static function logout()
	{
		header('Location: '.self::URL.'logout?service=http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']));//ou SCRIPT_NAME?
		// On n'utilise pas REQUEST_URI sinon cela déconnecterait à l'infini.
	}
}

class KoalaClient {
  protected $url = "";
  protected $useragent = "KoalaClient/0.1";

  protected function apiCall($endpoint, $params = array(), $method = "GET") {
    // Construction de la chaîne de paramètres
    $paramstring = "";
    if (!empty($params)) {
      foreach ($params as $key => $param) {
        $paramstring .= $key . "=" . $param . "&";
      }
      // On supprimer le dernier &
      $paramstring = substr($paramstring, 0, -1);
    } // $paramstring = "key=[clé]&"
    
    // Réglages de cURL
    $settings = array(
      CURLOPT_USERAGENT => $this->useragent,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_CAINFO => __DIR__. '/../Resources/certificates/TERENA_SSL_CA.pem',
    );
    
    // Construction de l'URL et des postfields
    if($method == "GET"){
      $url = $this->url . $endpoint . "?" . $paramstring;
    }
    else {
      $url = $this->url . $endpoint;
      $settings[CURLOPT_POSTFIELDS] = $params;
    }
    
    // Initialisation de cURL
    $ch = curl_init($url);
    curl_setopt_array($ch, $settings);

    // Éxécution de la requête
    $result = curl_exec($ch);
    
    // Si erreur d'appel de cron
    if (curl_errno($ch) != 0) {
      throw new ApiException(503);
    }
    // Si erreur http, on la renvoie
    else if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
      throw new ApiException(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    }
    // Sinon, on renvoie les infos
    else {
      return json_decode($result);
    }
  }
}

class GingerClient extends KoalaClient {
  private $key;

  public function __construct($key, $url="https://assos.utc.fr/ginger/v1/"){
    $this->url = $url;
    $this->key = $key;
  }
  
  public function apiCall($endpoint, $params = array(), $method = "GET") {
    // Ajout de la clé aux requêtes et appel du parent
    $params["key"] = $this->key;
    return parent::apiCall($endpoint, $params, $method);
  }
  
  /**
   * Récupérer un utilisateur à partir d'un login.
   *
   * @param string $login Login
   * @return object Utilisateur
   */
  public function getUser($login) {
    return $this->apiCall($login);
  }
  
  /**
   * Récupérer un utilisateur à partir d'un id de badge
   * (si la clé l'autorise).
   *
   * @param string $badge Identifiant de badge
   * @return object Utilisateur
   */
  public function getCard($badge) {
    return $this->apiCall("badge/$badge");
  }
  
  public function findPersonne($loginPart) {
    return $this->apiCall("find/$loginPart");
  }
  
  public function getCotisations($login) {
    return $this->apiCall("$login/cotisations");
  }
  
  public function addCotisation($login, $debut, $fin, $montant){
    $params = array(
      "debut" => $debut,
      "fin" => $fin,
      "montant" => $montant,
    );
    return $this->apiCall("$login/cotisations", $params, "POST");
  }
  
  /**
   * Récupérer l'historique des cotisations par semestre
   * (nécessite une clé avec le droit cotisation).
   *
   * @return array Stats (semestre => nombre de cotisations)
   */
  public function getStats() {
    return $this->apiCall("stats");
  }

 public function getExts() {
    return $this->apiCall("exts");
  }
  
  public function setPersonne($login, $nom, $prenom, $mail, $is_adulte){
    $params = array(
      "nom" => $nom,
      "prenom" => $prenom,
      "is_adulte" => $is_adulte,
    );
    return $this->apiCall("$login/edit", $params, "POST");
  }
}

class ApiException extends \Exception {
  static $http = array (
    100 => "Continue",
    101 => "Switching Protocols",
    200 => "OK",
    201 => "Created",
    202 => "Accepted",
    203 => "Non-Authoritative Information",
    204 => "No Content",
    205 => "Reset Content",
    206 => "Partial Content",
    300 => "Multiple Choices",
    301 => "Moved Permanently",
    302 => "Found",
    303 => "See Other",
    304 => "Not Modified",
    305 => "Use Proxy",
    307 => "Temporary Redirect",
    400 => "Bad Request",
    401 => "Non autorisé : une clé d'accès est nécessaire pour exécuter cette requête",
    402 => "Payment Required",
    403 => "Interdit : l'authentification est refusée",
    404 => "Non trouvé : la ressource demandée n'existe pas",
    405 => "Method Not Allowed",
    406 => "Not Acceptable",
    407 => "Proxy Authentication Required",
    408 => "Request Time-out",
    409 => "Conflict",
    410 => "Gone",
    411 => "Length Required",
    412 => "Precondition Failed",
    413 => "Request Entity Too Large",
    414 => "Request-URI Too Large",
    415 => "Unsupported Media Type",
    416 => "Requested range not satisfiable",
    417 => "Expectation Failed",
    500 => "Internal Server Error",
    501 => "Not Implemented",
    502 => "Bad Gateway",
    503 => "Service Unavailable",
    504 => "Gateway Time-out"        
  );
  public function __construct($code) {
    parent::__construct($this::$http[$code], $code);
    $this->code = $code;
  }
}

function checkIfUserInDatabase($userRecupere){
  if (gettype($userRecupere) == 'NULL') return false;
  // rend faux si l'user n'est pas dans la database
  else return true;
  // rend true si l'user est dans la database
}

class connexionController extends Controller
{

  private $gingerKey;

    function recuperationLogin(){
      //retourne -1 si récupération impossible
      // sinon, lance une exception "Impossible de se connecter"

      $data = file_get_contents('https://cas.utc.fr/cas/serviceValidate?service=http://'.$_SERVER["HTTP_HOST"].$this->get('router')->generate('sdf_billetterie_connexionCAS').'&ticket='.$_GET['ticket']);
      if (empty($data)) throw new Exception("Impossible de se connecter : Impossible d'obtenir un ticket du CAS");
      $parsed = new xmlToArrayParser($data);
      if (!isset($parsed->array['cas:serviceResponse']['cas:authenticationSuccess']['cas:user'])) throw new Exception("Impossible de se connecter : Impossible d'obtenir le login du retour CAS");
      //return $parsed->array['cas:serviceResponse']['cas:authenticationSuccess']['cas:user'];
      return $parsed;
    }

    public function connexionCASAction()
    {
      $em = $this->getDoctrine()->getManager();
      if(session_id() != ''
        && isset($_SESSION['typeUser'])
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
        if (!checkIfUserInDatabase($UserDeCeLogin)){
          // l'user n'est pas dans la BDD

          $ginger = new GingerClient($gingerKey, "https://assos.utc.fr/ginger/v1/");
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
      return $this->render('SDFBilletterieBundle:Default:inscription.html.twig', array('inscriptionError' => false, 'mdpProblem' => false, 'birthdayProblem' => false, 'loginExists' => false));
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
      if (checkIfUserInDatabase($UserDeCeLogin)) throw new Exception("Utilisateur déjà existant");
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
      if (checkIfUserInDatabase($UserDeCeLogin)) $logininvalide = true;
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
      if (!checkIfUserInDatabase($UserDeCeLogin)) return $this->render('SDFBilletterieBundle:Default:index.html.twig', array('connexionError' => true, 'inscriptionReussie' => false,'accesExterieur' => false));
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

    public function decoAction(){
      if(session_id() != '') {
        session_destroy();
      }
      return $this->redirect('https://cas.utc.fr/cas/logout');
    }
}
