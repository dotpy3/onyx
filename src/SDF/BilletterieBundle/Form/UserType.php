<?php

namespace SDF\BilletterieBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserType extends AbstractType
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('username', 'text')
			->add('firstname', 'text')
			->add('name', 'text')
			->add('email', 'email')
			->add('password', 'password')
			->add('birthdate', 'date')
		;
	}

	/**
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'SDF\BilletterieBundle\Entity\User'
		));
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'sdf_billetteriebundle_user';
	}
}
