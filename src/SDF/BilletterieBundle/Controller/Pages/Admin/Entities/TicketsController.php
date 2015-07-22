<?php

namespace SDF\BilletterieBundle\Controller\Pages\Admin\Entities;

use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Controller\Pages\Admin\CrudController;
use SDF\BilletterieBundle\Entity\Billet;
use SDF\BilletterieBundle\Form\Admin\BilletType;

class TicketsController extends CrudController
{
	public function listAction()
	{
		return $this->listEntities('Billet', 'SDFBilletterieBundle', 'SDFBilletterieBundle');
	}

	public function newAction()
	{
		return $this->renderCreationForm(new Billet(), new BilletType(), 'Billet', 'SDFBilletterieBundle');
	}

	public function createAction(Request $request)
	{
		return $this->createEntity($request, new Billet(), new BilletType(), 'Billet', 'SDFBilletterieBundle', 'sdf_billetterie_administration_tickets_list');
	}
}
