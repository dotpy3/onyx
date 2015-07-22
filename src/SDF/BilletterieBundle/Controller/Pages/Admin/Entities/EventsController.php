<?php

namespace SDF\BilletterieBundle\Controller\Pages\Admin\Entities;

use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Controller\Pages\Admin\CrudController;
use SDF\BilletterieBundle\Entity\Evenement;
use SDF\BilletterieBundle\Form\Admin\EventType;

class EventsController extends CrudController
{
	public function newAction()
	{
		return $this->renderCreationForm(new Evenement(), new EventType(), 'Evenement', 'SDFBilletterieBundle');
	}

	public function createAction(Request $request)
	{
		return $this->renderCreationForm($request, new Evenement(), new EventType(), 'Evenement', 'SDFBilletterieBundle', 'sdf_billetterie_administration');
	}
}