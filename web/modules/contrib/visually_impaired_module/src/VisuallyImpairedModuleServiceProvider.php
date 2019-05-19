<?php

namespace Drupal\visually_impaired_module;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the language manager service.
 */
class VisuallyImpairedModuleServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('http_middleware.page_cache');
    $definition->setClass('Drupal\visually_impaired_module\StackMiddleware\MyCache');
  }

}
