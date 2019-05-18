<?php
namespace Drupal\jalalidate;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * JalaliDate dependency injection container.
 */
class JalaliDateBundle extends Bundle {

	/**
	 * Implements \Symfony\Component\HttpKernel\Bundle\BundleInterface::build().
	 */
	public function build(ContainerBuilder $container) {
		$container->addCompilerPass(new JalaliDatePass());
	}
}