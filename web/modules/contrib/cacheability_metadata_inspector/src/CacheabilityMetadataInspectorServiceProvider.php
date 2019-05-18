<?php

namespace Drupal\cacheability_metadata_inspector;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Defines a service modifier/provider for the module.
 */
class CacheabilityMetadataInspectorServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Use our version of the renderer.
    $container->getDefinition('renderer')->setClass(CacheabilityMetadataRenderer::class);
  }

}
