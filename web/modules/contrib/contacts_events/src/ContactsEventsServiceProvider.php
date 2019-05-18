<?php

namespace Drupal\contacts_events;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Custom service provider for Contacts Events.
 */
class ContactsEventsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Replace the Commerce Checkout Order Manager with our custom version.
    $definition = $container->getDefinition('commerce_checkout.checkout_order_manager');
    $definition->setClass('Drupal\contacts_events\CustomCheckoutOrderManager');
  }

}
