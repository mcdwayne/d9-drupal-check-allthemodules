<?php

namespace Drupal\kml;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Adds kml+xml and kmz as known formats.
 */
class KmlServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('http_middleware.negotiation') && is_a($container->getDefinition('http_middleware.negotiation')->getClass(), '\Drupal\Core\StackMiddleware\NegotiationMiddleware', TRUE)) {
      $container->getDefinition('http_middleware.negotiation')->addMethodCall('registerFormat', ['kml', ['application/vnd.google-earth.kml+xml']]);
      $container->getDefinition('http_middleware.negotiation')->addMethodCall('registerFormat', ['kmz', ['application/vnd.google-earth.kmz']]);
    }
  }

}
