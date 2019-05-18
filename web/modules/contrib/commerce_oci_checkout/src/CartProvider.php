<?php

namespace Drupal\commerce_oci_checkout;

use Drupal\commerce_cart\CartProvider as CartProviderOriginal;
use Drupal\commerce_cart\CartSessionInterface;
use Drupal\commerce_cart\Exception\DuplicateCartException;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Custom cart provider.
 */
class CartProvider extends CartProviderOriginal {

  const ATTRIBUTE_KEY = 'oci_cart';

  /**
   * Attribute bag.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface
   */
  protected $attributeBag;

  /**
   * CartProvider constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentStoreInterface $current_store, AccountInterface $current_user, CartSessionInterface $cart_session, AttributeBagInterface $attribute_bag) {
    parent::__construct($entity_type_manager, $current_store, $current_user, $cart_session);
    $this->attributeBag = $attribute_bag;
  }

  /**
   * {@inheritdoc}
   */
  public function loadCartData(AccountInterface $account = NULL) {
    if ($this->shouldUseParent()) {
      return parent::loadCartData($account);
    }
    $data = [];
    if ($cart = $this->attributeBag->get(self::ATTRIBUTE_KEY)) {
      $data[$cart->id()] = [
        'type' => $cart->bundle(),
        'store_id' => $cart->getStoreId(),
      ];
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function createCart($order_type, StoreInterface $store = NULL, AccountInterface $account = NULL) {
    if ($this->shouldUseParent()) {
      return parent::createCart($order_type, $store, $account);
    }
    $store = $store ?: $this->currentStore->getStore();
    $account = $account ?: $this->currentUser;
    $uid = $account->id();
    $store_id = $store->id();
    if ($this->getCartId($order_type, $store, $account)) {
      // Don't allow multiple cart orders matching the same criteria.
      throw new DuplicateCartException("A cart order for type '$order_type', store '$store_id' and account '$uid' already exists.");
    }

    // Create the new cart order.
    $cart = $this->orderStorage->create([
      'type' => $order_type,
      'store_id' => $store_id,
      'uid' => $uid,
      'cart' => TRUE,
    ]);
    $cart->save();
    $this->attributeBag->set(self::ATTRIBUTE_KEY, $cart);
    return $cart;
  }

  /**
   * Helper.
   */
  protected function shouldUseParent() {
    if ($this->currentUser->id() == 1) {
      // Admin never gets that.
      return TRUE;
    }
    if (!$this->currentUser->hasPermission('use commerce_oci_checkout')) {
      return TRUE;
    }
    return FALSE;
  }

}
