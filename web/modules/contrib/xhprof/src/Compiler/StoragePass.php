<?php

namespace Drupal\xhprof\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class StoragePass
 */
class StoragePass implements CompilerPassInterface {

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *
   * @throws \InvalidArgumentException
   */
  public function process(ContainerBuilder $container) {
    if (FALSE === $container->hasDefinition('xhprof.storage_manager')) {
      return;
    }

    $definition = $container->getDefinition('xhprof.storage_manager');

    foreach ($container->findTaggedServiceIds('xhprof_storage') as $id => $attributes) {
      $definition->addMethodCall('addStorage', array($id, new Reference($id)));
    }
  }
}
