<?php

namespace SDF\BilletterieBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Entity\User;
use SDF\BilletterieBundle\Form\UserType;

class AuthenticationController extends FrontController
{
	// IMPORTANT
	// Exterior users login is currently handled by the home page --> See PagesController::homeAction().
	public function casCallbackAction()
	{
		$this->addFlash('success', 'Vous êtes bien connecté !');

		return $this->redirectToRoute('sdf_billetterie_homepage');
	}

	public function subscribeAction()
	{
		$user = new User();
		$form = $this->createForm(new UserType(), $user);

		return $this->render('SDFBilletterieBundle:Pages/Authentication:subscription.html.twig', array(
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

			return $this->redirectToRoute('sdf_billetterie_homepage');
		}

		return $this->render('SDFBilletterieBundle:Pages/Authentication:subscription.html.twig', array(
			'form' => $form->createView()
		));
	}
}
