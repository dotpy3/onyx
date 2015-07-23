<?php

namespace SDF\BilletterieBundle\Controller\Billetterie\Admin\Entities;

use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Controller\Billetterie\Admin\CrudController;
use SDF\BilletterieBundle\Entity\Trajet;
use SDF\BilletterieBundle\Form\Admin\TrajetType;

class JourneysController extends CrudController
{
	public function listAction()
	{
		return $this->listEntities('Trajet', 'SDFBilletterieBundle', 'SDFBilletterieBundle');
	}

	public function newAction()
	{
		return $this->renderCreationForm(new Trajet(), new TrajetType(), 'Trajet', 'SDFBilletterieBundle');
	}

	public function createAction(Request $request)
	{
		return $this->createEntity($request, new Trajet(), new TrajetType(), 'Trajet', 'SDFBilletterieBundle', 'sdf_billetterie_administration_journeys_list');
	}
}