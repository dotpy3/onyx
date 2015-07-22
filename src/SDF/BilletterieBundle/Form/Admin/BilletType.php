<?php

namespace SDF\BilletterieBundle\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BilletType extends AbstractType
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('prenom', 'text')
			->add('nom', 'text')
			->add('accepteDroitImage', 'checkbox', array('required' => false))
			->add('idPayutc', 'text')
			->add('isMajeur', 'checkbox', array('required' => false))
			->add('valide', 'checkbox', array('required' => false))
			->add('barcode', 'text')
			->add('dateAchat', 'datetime')
		;


		$builder
			->add('navette', 'entity', array(
				'required' => false,
				'class' => 'SDFBilletterieBundle:Navette',
				'expanded' => true,
				'empty_value' => 'Sans navette',
				'empty_data' => null
			))
		;


		$builder
			->add('user', 'entity', array(
				'required' => true,
				'class' => 'SDFBilletterieBundle:User'
			))
		;


		$builder
			->add('tarif', 'entity', array(
				'required' => true,
				'class' => 'SDFBilletterieBundle:Tarif'
			))
		;
	}

	/**
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'SDF\BilletterieBundle\Entity\Billet'
		));
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'sdf_billetteriebundle_administration_billet';
	}
}
