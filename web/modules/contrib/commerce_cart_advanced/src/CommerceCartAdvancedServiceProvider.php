<?php

namespace Drupal\commerce_cart_advanced;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Custom service provider implementation.
 *
 * Replaces the default cart provider with our own.
 *
 * @see \Drupal\commerce_cart_advanced\AdvancedCartProvider
 */
class CommerceCartAdvancedServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('commerce_cart.cart_provider');

    // Replace the default cart provider with our own.
    $definition->setClass('Drupal\commerce_cart_advanced\AdvancedCartProvider');

    // Add the extra argument (database connection service) that our cart
    // provider requires.
    $definition->addArgument(new Reference('database'));
  }

}
