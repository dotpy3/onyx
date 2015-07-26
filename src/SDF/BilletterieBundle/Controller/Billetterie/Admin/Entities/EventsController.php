<?php

namespace SDF\BilletterieBundle\Controller\Billetterie\Admin\Entities;

use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Controller\Billetterie\Admin\CrudController;
use SDF\BilletterieBundle\Entity\Evenement;
use SDF\BilletterieBundle\Form\Admin\EventType;

class EventsController extends CrudController
{
	public function listAction()
	{
		return $this->listEntities('Evenement', 'SDFBilletterieBundle', 'SDFBilletterieBundle');
	}

	public function newAction()
	{
		return $this->renderCreationForm(new Evenement(), new EventType(), 'Evenement', 'SDFBilletterieBundle');
	}

	public function createAction(Request $request)
	{
		return $this->createEntity($request, new Evenement(), new EventType(), 'Evenement', 'SDFBilletterieBundle', 'sdf_billetterie_administration_events_list');
	}
}