<?php

namespace Drupal\commerce_vipps\Resolver;

/**
 * Returns the site's default remote order id.
 */
class DefaultOrderIdResolver implements OrderIdResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    return uniqid();
  }

}
