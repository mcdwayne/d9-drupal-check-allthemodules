<?php

namespace Drupal\commerce_product_reservation;

use Drupal\commerce\AvailabilityManagerInterface;
use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product_reservation\Exception\AvailabilityException;
use Drupal\commerce_product_reservation\Exception\NoStockResultException;
use Drupal\commerce_product_reservation\Exception\OutOfStockException;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * CartAdder service.
 */
class CartAdder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * Availability manager.
   *
   * @var \Drupal\commerce\AvailabilityManagerInterface
   */
  protected $availabilityManager;

  /**
   * Current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * Cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Selected store.
   *
   * @var \Drupal\commerce_product_reservation\SelectedStoreManager
   */
  protected $selectedStore;

  /**
   * Store plugin manager.
   *
   * @var \Drupal\commerce_product_reservation\ReservationStorePluginManager
   */
  protected $storePluginManager;

  /**
   * Constructs a cartadder object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CartManagerInterface $cartManager, AvailabilityManagerInterface $availabilityManager, CurrentStoreInterface $current_store, CartProviderInterface $cart_provider, AccountProxyInterface $current_user, SelectedStoreManager $selectedStore, ReservationStorePluginManager $storePluginManager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cartManager = $cartManager;
    $this->availabilityManager = $availabilityManager;
    $this->currentStore = $current_store;
    $this->cartProvider = $cart_provider;
    $this->currentUser = $current_user;
    $this->selectedStore = $selectedStore;
    $this->storePluginManager = $storePluginManager;
  }

  /**
   * Add by SKU.
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\commerce_product_reservation\Exception\NoStockResultException
   * @throws \Drupal\commerce_product_reservation\Exception\OutOfStockException
   * @throws \Drupal\commerce_product_reservation\Exception\AvailabilityException
   */
  public function addBySku($sku, $quantity = 1) {
    /** @var \Drupal\commerce_product\ProductVariationStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('commerce_product_variation');
    if (!$variation = $storage->loadBySku($sku)) {
      throw new \Exception('No product found for SKU ' . $sku);
    }
    return $this->addEntity($variation, $quantity);
  }

  /**
   * Helper.
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\commerce_product_reservation\Exception\NoStockResultException
   * @throws \Drupal\commerce_product_reservation\Exception\OutOfStockException
   * @throws \Drupal\commerce_product_reservation\Exception\AvailabilityException
   */
  public function addEntity(ProductVariationInterface $variation, $quantity) {
    $current_store = $this->selectedStore->getSelectedStore();
    if (!$current_store) {
      throw new \InvalidArgumentException('Trying to add to a reservation cart without having a selected store');
    }
    // Load the plugin. This can trigger an exception, which is fine if we can
    // not find it.
    /** @var \Drupal\commerce_product_reservation\ReservationStoreInterface $plugin */
    $plugin = $this->storePluginManager->createInstance($current_store->getProvider());
    /** @var \Drupal\commerce_product_reservation\StockResult[] $stock_results */
    $stock_results = $plugin->getStockByStoresAndProducts([$current_store], [$variation->getSku()]);
    if (empty($stock_results)) {
      throw new NoStockResultException('No stock result found before adding it to cart');
    }
    $stock_result = reset($stock_results);
    if ($stock_result->getStock() < $quantity) {
      $e = new OutOfStockException('Quantity was bigger than the stock value');;
      $e->setMaxQuantity($stock_result->getStock());
      throw $e;
    }
    $store = $this->selectStore($variation);
    // Now see if it is at all possible to add it. Seems like this should be
    // unnecessary, so we have this issue opened for it:
    // https://www.drupal.org/project/commerce/issues/3023417.
    // If that ever gets fixed, this part should be removed.
    $context = new Context($this->currentUser, $store);
    if (!$this->availabilityManager->check($variation, $quantity, $context)) {
      throw new AvailabilityException('The item could was not allowed to add to the cart');
    }
    $cart = $this->cartProvider->getCart(ReservationManagerInterface::ORDER_TYPE, $store, $this->currentUser);
    if (!$cart) {
      $cart = $this->cartProvider->createCart(ReservationManagerInterface::ORDER_TYPE, $store, $this->currentUser);
    }
    return $this->cartManager->addEntity($cart, $variation, $quantity);
  }

  /**
   * Selects the store for the given purchasable entity.
   *
   * If the entity is sold from one store, then that store is selected.
   * If the entity is sold from multiple stores, and the current store is
   * one of them, then that store is selected.
   *
   * This is pure copy-paste from AddToCartForm.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The entity being added to cart.
   *
   * @throws \Exception
   *   When the entity can't be purchased from the current store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The selected store.
   *
   * @todo: Remove when (if) this lands:
   * https://www.drupal.org/project/commerce/issues/3006114#comment-12889570
   *
   * @see \Drupal\commerce_cart\Form\AddToCartForm::selectStore()
   */
  protected function selectStore(PurchasableEntityInterface $entity) {
    $stores = $entity->getStores();
    if (count($stores) === 1) {
      $store = reset($stores);
    }
    elseif (count($stores) === 0) {
      // Malformed entity.
      throw new \Exception('The given entity is not assigned to any store.');
    }
    else {
      $store = $this->currentStore->getStore();
      if (!in_array($store, $stores)) {
        // Indicates that the site listings are not filtered properly.
        throw new \Exception("The given entity can't be purchased from the current store.");
      }
    }

    return $store;
  }

}
