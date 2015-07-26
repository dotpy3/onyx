<?php

namespace SDF\BilletterieBundle\Controller\Billetterie\Admin\Entities;

use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Controller\Billetterie\Admin\CrudController;
use SDF\BilletterieBundle\Entity\PotCommunTarifs;
use SDF\BilletterieBundle\Form\Admin\PotCommunTarifsType;

class PriceGroupsController extends CrudController
{
	public function listAction()
	{
		return $this->listEntities('PotCommunTarifs', 'SDFBilletterieBundle', 'SDFBilletterieBundle');
	}

	public function newAction()
	{
		return $this->renderCreationForm(new PotCommunTarifs(), new PotCommunTarifsType(), 'PotCommunTarifs', 'SDFBilletterieBundle');
	}

	public function createAction(Request $request)
	{
		return $this->createEntity($request, new PotCommunTarifs(), new PotCommunTarifsType(), 'PotCommunTarifs', 'SDFBilletterieBundle', 'sdf_billetterie_administration_price_groups_list');
	}
}