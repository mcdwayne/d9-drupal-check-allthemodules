<?php

namespace Drupal\syncart\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Cart' Block.
 *
 * @Block(
 *   id = "small_cart_block",
 *   admin_label = @Translation("Small cart block"),
 *   category = @Translation("Small cart block"),
 * )
 */
class CartBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cart = \Drupal::service('syncart.cart');
    return [
      '#theme' => 'syncart-cart-small',
      '#data' => [
        'cart' => $cart->renderCartPageInfo(),
      ],
    ];
  }

}
