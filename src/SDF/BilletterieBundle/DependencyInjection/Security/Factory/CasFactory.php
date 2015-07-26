<?php

namespace SDF\BilletterieBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

/**
 * CasFactory
 * Define a pre authentication process to use the CAS authentication
 *
 * @author Matthieu Guffroy <mattgu74@gmail.com>
 * @author Florent Schildknecht <florent.schildknecht@gmail.com>
 */
class CasFactory implements SecurityFactoryInterface
{
	public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
	{
		$providerId = 'authentication.provider.cas.'.$id;
		$container
			->setDefinition($providerId, new DefinitionDecorator('cas.authentication.provider'))
			->replaceArgument(0, new Reference($userProvider))
		;

		$listenerId = 'authentication.listener.cas.'.$id;
		$listener = $container->setDefinition($listenerId, new DefinitionDecorator('cas.authentication.listener'));

		return array($providerId, $listenerId, $defaultEntryPoint);
	}

	public function getPosition()
	{
		return 'pre_auth';
	}

	public function getKey()
	{
		return 'cas_authentication';
	}

	public function addConfiguration(NodeDefinition $node)
	{
	}
}
