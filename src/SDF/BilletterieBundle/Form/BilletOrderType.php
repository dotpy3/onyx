<?php

namespace SDF\BilletterieBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BilletOrderType extends AbstractType
{
	private $shuttles;

	public function __construct(array $shuttles)
	{
		$this->shuttles = $shuttles;

		return $this;
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('nom', 'text', array('label' => 'Nom', 'required' => true))
			->add('prenom', 'text', array('label' => 'Prénom', 'required' => true))
			->add('accepteDroitImage', 'checkbox', array('label' => 'Je donne le droit à l\'image pour l\'évènement', 'required' => false))
		;

		if ($this->shuttles) {
			$builder
				->add('navette', 'entity', array(
					'required' => false,
					'class' => 'SDFBilletterieBundle:Navette',
					'empty_value' => 'Sans navette',
					'choices' => $this->shuttles
				))
			;
		} else {
			$builder->add('navette', 'hidden');
		}
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
		return 'sdf_billetteriebundle_billet_order';
	}
}
