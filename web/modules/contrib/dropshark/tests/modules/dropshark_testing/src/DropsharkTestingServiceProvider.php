<?php

namespace Drupal\dropshark_testing;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class DropsharkTestingServiceProvider.
 */
class DropsharkTestingServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    parent::alter($container);

    // Use a different class for the "dropshark.request" service in order to
    // mimic responses from the DropShark backend.
    $container->getDefinition('dropshark.request')
      ->setClass('Drupal\dropshark_testing\TestRequest')
      ->setArguments([new Reference('state')]);
  }

}
