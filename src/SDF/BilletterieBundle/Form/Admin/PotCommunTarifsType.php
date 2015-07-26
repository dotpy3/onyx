<?php

namespace SDF\BilletterieBundle\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PotCommunTarifsType extends AbstractType
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('titre','text')
		;
	}

	/**
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'SDF\BilletterieBundle\Entity\PotCommunTarifs'
		));
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'sdf_billetteriebundle_administration_pot_commun_tarif';
	}
}
