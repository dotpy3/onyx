<?php

namespace SDF\BilletterieBundle\Controller\Pages\Admin\Entities;

use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Controller\Pages\Admin\CrudController;
use SDF\BilletterieBundle\Entity\Billet;
use SDF\BilletterieBundle\Form\Admin\BilletType;

class TicketsController extends CrudController
{
	public function newAction()
	{
		return $this->renderCreationForm(new Billet(), new BilletType(), 'Billet', 'SDFBilletterieBundle');
	}

	public function createAction(Request $request)
	{
		return $this->renderCreationForm($request, new Billet(), new BilletType(), 'Billet', 'SDFBilletterieBundle', 'sdf_billetterie_administration');
	}
}
