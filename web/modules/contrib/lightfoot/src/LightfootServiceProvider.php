<?php
namespace Drupal\lightfoot;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
//use Symfony\Component\DependencyInjection\Reference;

// Related documentation:
// https://www.drupal.org/node/2026959

/**
 * Modifies the asset resolver service.
 */
class LightfootServiceProvider extends ServiceProviderBase {
  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    header('Lightfoot: initializing');

    // Replace core services with ones from Lightfoot.
    $definition = $container->getDefinition('asset.resolver');
    $definition->setClass('Drupal\lightfoot\LightfootAssetResolver');

    $definition = $container->getDefinition('asset.css.collection_optimizer');
    $definition->setClass('Drupal\lightfoot\LightfootCssCollectionOptimizer');

    $definition = $container->getDefinition('asset.js.collection_optimizer');
    $definition->setClass('Drupal\lightfoot\LightfootJsCollectionOptimizer');
    //$definition->addArgument(\Drupal::service('private_key')->get());
  }
}

