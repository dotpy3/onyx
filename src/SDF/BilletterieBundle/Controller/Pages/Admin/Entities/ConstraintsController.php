<?php

namespace SDF\BilletterieBundle\Controller\Pages\Admin\Entities;

use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Controller\Pages\Admin\CrudController;
use SDF\BilletterieBundle\Entity\Contraintes;
use SDF\BilletterieBundle\Form\Admin\ContraintesType;

class ConstraintsController extends CrudController
{
	public function newAction()
	{
		return $this->renderCreationForm(new Contraintes(), new ContraintesType(), 'Contraintes', 'SDFBilletterieBundle');
	}

	public function createAction(Request $request)
	{
		return $this->renderCreationForm($request, new Contraintes(), new ContraintesType(), 'Contraintes', 'SDFBilletterieBundle', 'sdf_billetterie_administration');
	}
}