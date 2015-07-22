<?php

namespace SDF\BilletterieBundle\Controller\Pages\Admin\Entities;

use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Controller\Pages\Admin\CrudController;
use SDF\BilletterieBundle\Entity\Tarif;
use SDF\BilletterieBundle\Form\Admin\TarifType;

class PricesController extends CrudController
{
	public function listAction()
	{
		return $this->listEntities('Tarif', 'SDFBilletterieBundle', 'SDFBilletterieBundle');
	}

	public function newAction()
	{
		return $this->renderCreationForm(new Tarif(), new TarifType(), 'Tarif', 'SDFBilletterieBundle');
	}

	public function createAction(Request $request)
	{
		return $this->createEntity($request, new Tarif(), new TarifType(), 'Tarif', 'SDFBilletterieBundle', 'sdf_billetterie_administration_prices_list');
	}
}