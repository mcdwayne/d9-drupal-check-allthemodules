<?php

namespace Drupal\disable_route_normalizer;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class DisableRouteNormalizerServiceProvider.
 *
 * @package Drupal\disable_route_normalizer
 */
class DisableRouteNormalizerServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('redirect.route_normalizer_request_subscriber');
    $definition->setClass('Drupal\disable_route_normalizer\DisableRouteNormalizer\DisableRouteNormalizer');
  }

}

