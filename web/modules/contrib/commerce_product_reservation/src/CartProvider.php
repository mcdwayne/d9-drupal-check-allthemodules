<?php

namespace Drupal\commerce_product_reservation;

use Drupal\commerce_cart\CartProvider as CommerceCartProvider;
use Drupal\Core\Session\AccountInterface;

/**
 * CartProvider service.
 */
class CartProvider extends CommerceCartProvider {

  /**
   * Whether to filter out our own carts or not.
   *
   * @var bool
   */
  protected $filterEnabled = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getCarts(AccountInterface $account = NULL) {
    $trace = debug_backtrace();
    $caller = $trace[1];
    $old_enabled = $this->filterEnabled;
    // If the caller is commerce_cart_user_login we make sure we do not filter,
    // so the cart can be assigned.
    if (isset($caller["function"]) && $caller["function"] == 'commerce_cart_user_login') {
      $this->setFilterEnabled(FALSE);
    }
    // We also support the commerce_combine_carts module.
    if (isset($caller['class']) && $caller['class'] == 'Drupal\commerce_combine_carts\CartUnifier') {
      $this->setFilterEnabled(FALSE);
    }
    $carts = parent::getCarts($account);
    if ($this->filterEnabled) {
      $carts = array_filter($carts, function ($cart) {
        /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
        return $cart->bundle() != 'reservation_order';
      });
    }
    // Reset filter, in case we override it ourself.
    $this->setFilterEnabled($old_enabled);
    return $carts;
  }

  /**
   * Set the filter setting.
   */
  public function setFilterEnabled($enabled) {
    $this->filterEnabled = $enabled;
  }

}
