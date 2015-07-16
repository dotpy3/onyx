<?php

namespace SDF\BilletterieBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ContraintesType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom')
            ->add('doitEtreCotisant')
            ->add('debutMiseEnVente')
            ->add('finMiseEnVente')
            ->add('accessibleExterieur')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'SDF\BilletterieBundle\Entity\Contraintes'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sdf_billetteriebundle_contraintes';
    }
}
