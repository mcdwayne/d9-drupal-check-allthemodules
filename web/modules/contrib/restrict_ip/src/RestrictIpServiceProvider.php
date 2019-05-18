<?php

namespace Drupal\restrict_ip;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Reference;

class RestrictIpServiceProvider implements ServiceProviderInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function register(ContainerBuilder $container)
	{
		$modules = $container->getParameter('container.modules');
		if(isset($modules['ip2country']))
		{
			$definition = $container->getDefinition('restrict_ip.service');
			$definition->addArgument(new Reference('user.data'));
		}
	}
}
