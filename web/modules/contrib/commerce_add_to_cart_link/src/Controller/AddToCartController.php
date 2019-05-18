<?php

namespace Drupal\commerce_add_to_cart_link\Controller;

use Drupal\commerce_add_to_cart_link\CartLinkTokenInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the add to cart controller.
 *
 * The controller enables product variations to be added via GET requests.
 */
class AddToCartController extends ControllerBase {

  /**
   * The cart link token service.
   *
   * @var \Drupal\commerce_add_to_cart_link\CartLinkTokenInterface
   */
  protected $cartLinkToken;

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
   * Constructs a new AddToCartController object.
   *
   * @param \Drupal\commerce_add_to_cart_link\CartLinkTokenInterface $cart_link_token
   *   The cart link token service.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_order\Resolver\OrderTypeResolverInterface $order_type_resolver
   *   The order type resolver.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   */
  public function __construct(CartLinkTokenInterface $cart_link_token, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, OrderTypeResolverInterface $order_type_resolver, CurrentStoreInterface $current_store) {
    $this->cartLinkToken = $cart_link_token;
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->orderTypeResolver = $order_type_resolver;
    $this->currentStore = $current_store;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_add_to_cart_link.token'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_order.chain_order_type_resolver'),
      $container->get('commerce_store.current_store')
    );
  }

  /**
   * Performs the add to cart action and redirects to cart.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $commerce_product
   *   The product entity.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $commerce_product_variation
   *   The product variation to add.
   * @param string $token
   *   The CSRF token.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the cart after adding the product.
   *
   * @throws \Exception
   *   When the call to self::selectStore() throws an exception because the
   *   entity can't be purchased from the current store.
   */
  public function action(ProductInterface $commerce_product, ProductVariationInterface $commerce_product_variation, $token) {
    $order_item = $this->cartManager->createOrderItem($commerce_product_variation);
    $order_type = $this->orderTypeResolver->resolve($order_item);

    $store = $this->selectStore($commerce_product_variation);
    $cart = $this->cartProvider->getCart($order_type, $store);
    if (!$cart) {
      $cart = $this->cartProvider->createCart($order_type, $store);
    }
    $this->cartManager->addOrderItem($cart, $order_item);

    return $this->redirect('commerce_cart.page');
  }

  /**
   * Access callback for the action route.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $commerce_product
   *   The product entity.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $commerce_product_variation
   *   The product variation to add.
   * @param string $token
   *   The CSRF token.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(ProductInterface $commerce_product, ProductVariationInterface $commerce_product_variation, $token) {
    if (!$commerce_product->isPublished() || !$commerce_product->access('view')) {
      // If product is disabled or the user has no view access, deny.
      return AccessResult::forbidden();
    }
    if (!$commerce_product_variation->isActive() || !$commerce_product_variation->access('view')) {
      // If the variation is inactive, deny.
      return AccessResult::forbidden();
    }
    if ((int) $commerce_product->id() !== (int) $commerce_product_variation->getProductId()) {
      // Deny, if the product ID and variation's parent product ID don't match.
      return AccessResult::forbidden();
    }

    return AccessResult::allowedIf($this->cartLinkToken->validate($commerce_product_variation, $token));
  }

  /**
   * Selects the store for the given variation.
   *
   * If the variation is sold from one store, then that store is selected.
   * If the variation is sold from multiple stores, and the current store is
   * one of them, then that store is selected.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The variation being added to cart.
   *
   * @throws \Exception
   *   When the variation can't be purchased from the current store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The selected store.
   */
  protected function selectStore(ProductVariationInterface $variation) {
    $stores = $variation->getStores();
    if (count($stores) === 1) {
      $store = reset($stores);
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
