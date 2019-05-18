<?php

/**
 * @file
 * Definition of Drupal\dfw\DfwBundle.
 */

namespace Drupal\dfw;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The bundle for dfw.module.
 */
class DfwBundle extends Bundle {
  public function build(ContainerBuilder $container) {
    $container->register('dfw.dfw_subscriber', 'Drupal\dfw\DfwSubscriber')
      ->addTag('event_subscriber');

    $container->register('dfw.autocomplete_controller', 'Drupal\dfw\DfwAutocompleteController')
      ->addArgument(new Reference('dfw.autocomplete'));
    $container->register('dfw.autocomplete', 'Drupal\dfw\DfwAutocomplete')
      ->addArgument(new Reference('database'))
      ->addArgument(new Reference('config.factory'));
  }
}