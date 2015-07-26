<?php

namespace SDF\BilletterieBundle\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EventType extends AbstractType
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('nom')
			->add('quantiteMax')
		;
	}

	/**
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'SDF\BilletterieBundle\Entity\Evenement'
		));
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'sdf_billetteriebundle_administration_event';
	}
}
