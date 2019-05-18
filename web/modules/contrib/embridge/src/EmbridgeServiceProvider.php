<?php

/**
 * @file
 * Contains \Drupal\embridge\EmbridgeServiceProvider.
 */

namespace Drupal\embridge;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class EmbridgeServiceProvider.
 */
class EmbridgeServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    // If entity_pilot exists, use it's entity resolver for our normalizer.
    if (isset($modules['entity_pilot'])) {
      $definition = $container->getDefinition('embridge.normalizer.asset.hal');

      // Overwrite our normalizer service args.
      $definition->setArguments([
        new Reference('hal.link_manager'),
        new Reference('entity_pilot.resolver.unsaved_uuid'),
      ]);

      $container->setDefinition('embridge.normalizer.asset.hal', $definition);
    }
  }

}
