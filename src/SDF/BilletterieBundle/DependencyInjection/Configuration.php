<?php

namespace SDF\BilletterieBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root('sdf_billetterie');

		$rootNode
			->children()
				->arrayNode('ginger')
					->children()
						->scalarNode('url')
							->info('The Ginger client API url')
						->end()
						->scalarNode('key')
							->isRequired()
							->cannotBeEmpty()
							->info('The Ginger client key')
						->end()
					->end()
				->end()
				->arrayNode('payutc')
					->children()
						->scalarNode('key')
							->isRequired()
							->cannotBeEmpty()
							->info('The PayUtc client key')
						->end()
						->integerNode('api_url')
							->info('The PayUtc API URL')
						->end()
						->integerNode('fundation_id')
							->isRequired()
							->cannotBeEmpty()
							->info('The PayUtc fundation ID')
						->end()
					->end()
				->end()
				->arrayNode('utc_cas')
					->children()
						->scalarNode('url')
							->isRequired()
							->cannotBeEmpty()
							->info('The UTC CAS connection URL')
						->end()
					->end()
				->end()
				->arrayNode('settings')
					->children()
						->booleanNode('enable_exterior_access')
							->defaultFalse()
							->info('Should the billetterie be open to non-UTC?')
						->end()
					->end()
				->end()
			->end()
		;

		return $treeBuilder;
	}
}
