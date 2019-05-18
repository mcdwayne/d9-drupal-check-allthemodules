<?php

namespace Drupal\entity_sanitizer_image_fallback;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the language manager service.
 */
class EntitySanitizerImageFallbackServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Override the public streamwrapper by our own implementation so image
    // requests can be intercepted.
    $container
      ->getDefinition('stream_wrapper.public')
      ->setClass('Drupal\entity_sanitizer_image_fallback\StreamWrapper\PublicStream');

    // The private stream wrapper is only available when the private file
    // system has been enabled.
    if ($container->hasDefinition('stream_wrapper.private')) {
      $container
        ->getDefinition('stream_wrapper.private')
        ->setClass('Drupal\entity_sanitizer_image_fallback\StreamWrapper\PrivateStream');
    }
  }
}
