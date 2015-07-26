<?php

namespace SDF\BilletterieBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SDFBilletterieExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sdf_billetterie', $config);

        $container->setParameter('sdf_billetterie.ginger', $config['ginger']);
        $container->setParameter('sdf_billetterie.ginger.url', $config['ginger']['url']);
        $container->setParameter('sdf_billetterie.ginger.key', $config['ginger']['key']);

        $container->setParameter('sdf_billetterie.payutc', $config['payutc']);
        $container->setParameter('sdf_billetterie.payutc.key', $config['payutc']['key']);
        $container->setParameter('sdf_billetterie.payutc.api_url', $config['payutc']['api_url']);
        $container->setParameter('sdf_billetterie.payutc.api_service', $config['payutc']['api_service']);
        $container->setParameter('sdf_billetterie.payutc.fundation_id', $config['payutc']['fundation_id']);

        $container->setParameter('sdf_billetterie.nemopay', $config['nemopay']);
        $container->setParameter('sdf_billetterie.nemopay.payment_url', $config['nemopay']['payment_url']);

        $container->setParameter('sdf_billetterie.utc_cas', $config['utc_cas']);
        $container->setParameter('sdf_billetterie.utc_cas.url', $config['utc_cas']['url']);

        $container->setParameter('sdf_billetterie.settings', $config['settings']);
        $container->setParameter('sdf_billetterie.settings.enable_exterior_access', $config['settings']['enable_exterior_access']);
        $container->setParameter('sdf_billetterie.settings.barcode', $config['settings']['barcode']);
        $container->setParameter('sdf_billetterie.settings.barcode.max_number', $config['settings']['barcode']['max_number']);

        $container->setParameter('sdf_billetterie.mails', $config['mails']);
        $container->setParameter('sdf_billetterie.mails.from', $config['mails']['from']);
        $container->setParameter('sdf_billetterie.mails.reply_to', $config['mails']['reply_to']);
        $container->setParameter('sdf_billetterie.mails.text_only', $config['mails']['text_only']);
        $container->setParameter('sdf_billetterie.mails.checkout_subject', $config['mails']['checkout_subject']);
        $container->setParameter('sdf_billetterie.mails.informations_subject', $config['mails']['informations_subject']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
