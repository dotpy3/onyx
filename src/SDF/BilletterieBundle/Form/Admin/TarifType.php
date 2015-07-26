<?php

namespace SDF\BilletterieBundle\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use SDF\BilletterieBundle\Form\ContraintesType;

class TarifType extends AbstractType
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('nomTarif','text')
			->add('prix','text')
			->add('quantite','text')
			->add('quantiteParPersonne','text')
			->add('idPayutc','text')
		;

		$builder
			->add('contraintes', 'entity', array(
				'class' => 'SDFBilletterieBundle:Contraintes',
				'property' => 'nom',
				'multiple' => false
			));

		$builder
			->add('potCommun', 'entity', array(
				'class' => 'SDFBilletterieBundle:PotCommunTarifs',
				'property' => 'Titre',
				'multiple' => false
			));

		$builder
			->add('evenement', 'entity', array(
				'class'    => 'SDFBilletterieBundle:Evenement',
				'property' => 'nom',
				'multiple' => false
			))
		;
	}

	/**
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'SDF\BilletterieBundle\Entity\Tarif'
		));
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'sdf_billetteriebundle_administration_tarif';
	}
}
