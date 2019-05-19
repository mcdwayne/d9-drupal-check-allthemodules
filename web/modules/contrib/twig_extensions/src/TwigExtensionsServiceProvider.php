<?php

namespace Drupal\twig_extensions;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Registers the twig services.
 */
class TwigExtensionsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Ensure the Intl PHP extension is available before adding the service.
    if (class_exists('IntlDateFormatter')) {
      $container->register('twig_extensions.twig.intl', '\Twig_Extensions_Extension_Intl')
        ->addTag('twig.extension');
    }
  }

}
