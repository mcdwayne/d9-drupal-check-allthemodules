<?php
namespace Drupal\jalalidate;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Replaces the date service by a persian date.
 */
class JalaliDatePass implements CompilerPassInterface {

	/**
	 * Replaces the date service by a persian date.
	 *
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 *   The container to process.
	 */
	public function process(ContainerBuilder $container) {
		$definition = $container->getDefinition('date');
		$definition->setClass('Drupal\jalalidate\JalaliDateFormatter');
	}

}
