<?php

namespace SDF\BilletterieBundle\Controller\Pages\Admin\Entities;

use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Controller\Pages\Admin\CrudController;
use SDF\BilletterieBundle\Entity\Navette;
use SDF\BilletterieBundle\Form\Admin\NavetteType;

class ShuttlesController extends CrudController
{
	public function listAction()
	{
		return $this->listEntities('Navette', 'SDFBilletterieBundle', 'SDFBilletterieBundle');
	}

	public function newAction()
	{
		return $this->renderCreationForm(new Navette(), new NavetteType(), 'Navette', 'SDFBilletterieBundle');
	}

	public function createAction(Request $request)
	{
		return $this->createEntity($request, new Navette(), new NavetteType(), 'Navette', 'SDFBilletterieBundle', 'sdf_billetterie_administration_shuttles_list');
	}
}