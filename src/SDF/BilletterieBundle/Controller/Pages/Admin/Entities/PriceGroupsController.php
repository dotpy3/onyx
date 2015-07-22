<?php

namespace SDF\BilletterieBundle\Controller\Pages\Admin\Entities;

use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Controller\Pages\Admin\CrudController;
use SDF\BilletterieBundle\Entity\PotCommunTarifs;
use SDF\BilletterieBundle\Form\Admin\PotCommunTarifsType;

class PriceGroupsController extends CrudController
{
	public function newAction()
	{
		return $this->renderCreationForm(new PotCommunTarifs(), new PotCommunTarifsType(), 'PotCommunTarifs', 'SDFBilletterieBundle');
	}

	public function createAction(Request $request)
	{
		return $this->renderCreationForm($request, new PotCommunTarifs(), new PotCommunTarifsType(), 'PotCommunTarifs', 'SDFBilletterieBundle', 'sdf_billetterie_administration');
	}
}