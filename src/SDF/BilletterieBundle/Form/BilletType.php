<?php

namespace SDF\BilletterieBundle\Form;

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
            ->add('valide','checkbox')
            ->add('idPayutc','text')
            ->add('nom','text')
            ->add('prenom','text')
            ->add('isMajeur','checkbox')
            ->add('accepteDroitImage','checkbox')
            ->add('barcode','text')
            ->add('dateAchat','datetime')
        ;


        $builder->add('navette', 'entity', array(
                'class' => 'SDFBilletterieBundle:Navette',
                'property' => 'horaireDepartFormat',
                'multiple' => false,
                'expanded' => true,
                'empty_value' => 'Sans navette',
                'empty_data' => null
            ));


        $builder->add('utilisateur', 'entity', array(
                'class' => 'SDFBilletterieBundle:Utilisateur',
                'property' => 'email',
                'multiple' => false
            ));


        $builder->add('tarif', 'entity', array(
                'class' => 'SDFBilletterieBundle:Tarif',
                'property' => 'nomTarif',
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
            'data_class' => 'SDF\BilletterieBundle\Entity\Billet'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sdf_billetteriebundle_billet';
    }
}
