<?php

namespace Drupal\cors_ui;

use Drupal\Core\DependencyInjection\Compiler\CorsCompilerPass;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * The CORS UI service provider.
 */
class CorsUiServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Remove the CorsCompilerPass and replace it with our own. This is because
    // the CorsCompilerPass removes the CORS service from the container if the
    // config is not enabled, but by default it runs before the config object
    // from the UI has had a chance to replace the config in the container.
    $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
    foreach ($passes as $i => $pass) {
      if ($pass instanceof CorsCompilerPass) {
        $passes[$i] = new CorsUiCompilerPass();
      }
    }
    $container->getCompilerPassConfig()->setBeforeOptimizationPasses($passes);
  }

}
