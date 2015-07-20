<?php

namespace SDF\BilletterieBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use SDF\BilletterieBundle\DependencyInjection\Security\Factory\CasFactory;

class SDFBilletterieBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new CasFactory());
    }
}