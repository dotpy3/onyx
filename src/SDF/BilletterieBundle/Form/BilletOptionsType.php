<?php

namespace SDF\BilletterieBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BilletOptionsType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', 'text')
            ->add('prenom', 'text')
            ->add('accepteDroitImage', 'checkbox')
            ->add('navette', 'entity', array(
                'required' => false,
                'class' => 'SDFBilletterieBundle:Navette',
                'property' => 'horaireDepartFormat',
                'empty_value' => 'Sans navette'
            ))
            ->add('save', 'submit')
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
        return 'sdf_billetteriebundle_billet_options';
    }
}
