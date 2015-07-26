<?php

namespace SDF\BilletterieBundle\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class NavetteType extends AbstractType
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('horaireDepart','time')
			->add('capaciteMax','integer')
		;


		$builder
			->add('trajet', 'entity', array(
				'class' => 'SDFBilletterieBundle:Trajet'
			))
		;
	}

	/**
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'SDF\BilletterieBundle\Entity\Navette'
		));
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'sdf_billetteriebundle_administration_navette';
	}
}
