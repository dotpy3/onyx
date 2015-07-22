<?php

namespace SDF\BilletterieBundle\Controller\Pages\Admin\Entities;

use Symfony\Component\HttpFoundation\Request;

use SDF\BilletterieBundle\Controller\Pages\Admin\CrudController;
use SDF\BilletterieBundle\Entity\Tarif;
use SDF\BilletterieBundle\Form\Admin\TarifType;

class PricesController extends CrudController
{
	public function newAction()
	{
		return $this->renderCreationForm(new Tarif(), new TarifType(), 'Tarif', 'SDFBilletterieBundle');
	}

	public function createAction(Request $request)
	{
		return $this->renderCreationForm($request, new Tarif(), new TarifType(), 'Tarif', 'SDFBilletterieBundle', 'sdf_billetterie_administration');
	}
}