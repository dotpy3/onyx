<?php

namespace SDF\BilletterieBundle\Controller;

use Exception;
use DateTime;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Constraints\Date;

use Ginger\Client\GingerClient;
use Ginger\Client\ApiException;

use SDF\BilletterieBundle\Form\UserType;
use SDF\BilletterieBundle\Entity\User;
use SDF\BilletterieBundle\Entity\CasUser;
use SDF\BilletterieBundle\Entity\Log;
use SDF\BilletterieBundle\Utils\XmlParser\XmlParser;

use SDF\BilletterieBundle\Exception\CasErrorException;

class AuthenticationController extends Controller
{
	// EXTERIOR USERS LOGIN IS CURRENTLY MANAGED BY THE HOME PAGE --> See PagesController::homeAction().
	public function casCallbackAction()
	{
		$this->addFlash('success', 'Vous êtes bien connecté !');

		return $this->redirectToRoute('sdf_billetterie_homepage');
	}

	public function subscribeAction()
	{
		$user = new User();
		$form = $this->createForm(new UserType(), $user);

		return $this->render('SDFBilletterieBundle:Default:inscription.html.twig', array(
			'form' => $form->createView()
		));
	}

	public function subscribeCheckAction(Request $request)
	{
		$user = new User();
		$form = $this->createForm(new UserType(), $user);

		$form->handleRequest($request);

		if ($form->isValid()) {
			$em = $this->getDoctrine()->getManager();

			$em->persist($user);
			$em->flush();

			$this->addFlash('success', 'Vous êtes bien enregistré !');

			return $this->redirect($this->generateUrl('index_page'));
		}

		return $this->render('SDFBilletterieBundle:Default:inscription.html.twig', array(
			'form' => $form->createView()
		));
	}

	public function adminAction()
	{
		return $this->render('SDFBilletterieBundle:Default:admin.html.twig');
	}
}
