<?php

namespace Drupal\syncart\Hook;

/**
 * ThemeHooks.
 */
class Theme {

  /**
   * Implements hook_theme().
   */
  public static function hook() {
    return [
      'syncart-cart-field' => [
        'template' => 'field/cart--field',
        'variables' => ['data' => []],
      ],
      'syncart-cart' => [
        'template' => 'layout/cart',
        'variables' => ['data' => []],
      ],
      'syncart-cart-small' => [
        'template' => 'block/cart--small',
        'variables' => ['data' => []],
      ],
      'syncart-favorite-field' => [
        'template' => 'field/favorite--field',
        'variables' => ['data' => []],
      ],
      'syncart-favorites' => [
        'template' => 'layout/favorites',
        'variables' => ['data' => []],
      ],
      'syncart-favorites' => [
        'template' => 'layout/favorites',
        'variables' => ['data' => []],
      ],
      'commerce_checkout_completion_message' => [
        'template' => 'commerce/commerce-checkout-completion-message',
        'base hook' => 'commerce_checkout_completion_message',
      ],
      'commerce_checkout_form' => [
        'template' => 'commerce/commerce-checkout-form',
        'base hook' => 'commerce_checkout_form',
      ],
      'commerce_order_receipt' => [
        'template' => 'commerce/commerce-order-receipt',
        'base hook' => 'commerce_order_receipt',
      ],
    ];
  }

}
