<?php

namespace Drupal\google_vision_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

class GoogleVisionTestServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides GoogleVisionApi class to mock the safe search feature.
    $definition = $container->getDefinition('google_vision.api');
    $definition->setClass('Drupal\google_vision_test\GoogleVisionAPIFake');
  }
}