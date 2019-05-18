<?php

namespace Drupal\commerce_cashpresso\Controller;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Cashpresso direct checkout controller.
 */
class DirectCheckoutController extends ControllerBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The order type resolver.
   *
   * @var \Drupal\commerce_order\Resolver\OrderTypeResolverInterface
   */
  protected $orderTypeResolver;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The chain base price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * Constructs a new DirectCheckoutController object.
   *
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_order\Resolver\OrderTypeResolverInterface $order_type_resolver
   *   The order type resolver.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   *   The chain base price resolver.
   */
  public function __construct(CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, OrderTypeResolverInterface $order_type_resolver, CurrentStoreInterface $current_store, ChainPriceResolverInterface $chain_price_resolver) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->orderTypeResolver = $order_type_resolver;
    $this->currentStore = $current_store;
    $this->chainPriceResolver = $chain_price_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_order.chain_order_type_resolver'),
      $container->get('commerce_store.current_store'),
      $container->get('commerce_price.chain_price_resolver')
    );
  }

  /**
   * Adds the given purchasable entity to the cart and redirects to checkout.
   *
   * @param string $entity_type
   *   The entity type. Validation is done in our access callback.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The a redirect response.
   */
  public function addToCart($entity_type, $entity_id) {
    /** @var \Drupal\commerce\PurchasableEntityInterface $entity */
    $entity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id);

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->entityTypeManager()->getStorage('commerce_order_item');

    $order_item = $order_item_storage->createFromPurchasableEntity($entity);
    $store = $this->selectStore($entity);
    $context = new Context($this->currentUser(), $store);
    $resolved_price = $this->chainPriceResolver->resolve($entity, $order_item->getQuantity(), $context);
    $order_item->setUnitPrice($resolved_price);

    $order_type_id = $this->orderTypeResolver->resolve($order_item);
    $cart = $this->cartProvider->getCart($order_type_id, $store);
    if (!$cart) {
      $cart = $this->cartProvider->createCart($order_type_id, $store);
    }
    $this->cartManager->addOrderItem($cart, $order_item);
    return $this->redirect('commerce_checkout.form', ['commerce_order' => $cart->id()]);
  }

  /**
   * Validate route params validity and user access.
   *
   * We need to validate that the given route parameters represent a valid
   * purchasable entity, and that the current user is allowed to access this
   * entity, as well as the checkout.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity id.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function access($entity_type, $entity_id) {
    // Early exit for users without checkout permission.
    if (!$this->currentUser()->hasPermission('access checkout')) {
      return AccessResult::forbidden();
    }

    // Invalid storage type.
    if (!$this->entityTypeManager()->hasHandler($entity_type, 'storage')) {
      return AccessResult::forbidden();
    }

    // Unable to find the entity requested.
    if (!$entity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id)) {
      return AccessResult::forbidden();
    }

    // Invalid entity type.
    if (!($entity instanceof PurchasableEntityInterface)) {
      return AccessResult::forbidden();
    }

    // Run access check of product variation against its parent product instead.
    if ($entity instanceof ProductVariationInterface) {
      $product = $entity->getProduct();
      if (empty($product)) {
        return AccessResult::forbidden();
      }
      return $product->access('view', $this->currentUser(), TRUE);
    }

    return $entity->access('view', $this->currentUser(), TRUE);
  }

  /**
   * Selects the store for the given purchasable entity.
   *
   * If the entity is sold from one store, then that store is selected.
   * If the entity is sold from multiple stores, and the current store is
   * one of them, then that store is selected.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The entity being added to cart.
   *
   * @throws \Exception
   *   When the entity can't be purchased from the current store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The selected store.
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
