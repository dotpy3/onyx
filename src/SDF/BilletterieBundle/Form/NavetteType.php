<?php

namespace SDF\BilletterieBundle\Form;

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
            ->add('capaciteMax','text')
        ;


        $builder->add('trajet', 'entity', array(
                'class' => 'SDFBilletterieBundle:Trajet',
                'property' => 'lieuDepart',
                'multiple' => false
            ))
            ->add('save','submit');
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
        return 'sdf_billetteriebundle_navette';
    }
}
