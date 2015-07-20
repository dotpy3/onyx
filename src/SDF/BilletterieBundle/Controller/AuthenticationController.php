<?php

namespace SDF\BilletterieBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use SDF\BilletterieBundle\Entity\User;
use SDF\BilletterieBundle\Form\UserType;

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

		return $this->render('SDFBilletterieBundle:Pages:inscription.html.twig', array(
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

			return $this->redirect($this->generateUrl('sdf_billetterie_homepage'));
		}

		return $this->render('SDFBilletterieBundle:Pages:inscription.html.twig', array(
			'form' => $form->createView()
		));
	}
}
