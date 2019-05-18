<?php

namespace Drupal\commerce_ajax_cart_message;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class CommerceAjaxCartMessageServiceProvider.
 */
class CommerceAjaxCartMessageServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Replace the server side add to cart messaging.
    if ($container->hasDefinition('commerce_cart.cart_subscriber')) {
      $definition = $container->getDefinition('commerce_cart.cart_subscriber');
      $definition->setClass('Drupal\commerce_ajax_cart_message\EventSubscriber\CommerceAjaxCartMessageSubscriber')
        ->addArgument(new Reference('request_stack'));
    }
  }

}
