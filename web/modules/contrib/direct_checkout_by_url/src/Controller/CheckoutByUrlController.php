<?php

namespace Drupal\direct_checkout_by_url\Controller;

use Drupal\commerce\AvailabilityManagerInterface;
use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_product\ProductVariationStorageInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Direct checkout by URL routes.
 */
class CheckoutByUrlController extends ControllerBase {

  /**
   * Variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorageInterface
   */
  protected $variationStorage;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Order type resolver.
   *
   * @var \Drupal\commerce_order\Resolver\OrderTypeResolverInterface
   */
  protected $orderTypeResolver;

  /**
   * Cart Manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * Cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * Current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * Availability manager.
   *
   * @var \Drupal\commerce\AvailabilityManagerInterface
   */
  protected $availabilityManager;

  /**
   * Constructs the controller object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, ImmutableConfig $config, OrderTypeResolverInterface $order_type_resolver, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, CurrentStoreInterface $current_store, AccountProxyInterface $current_user, AvailabilityManagerInterface $availability_manager) {
    $this->variationStorage = $entity_type_manager->getStorage('commerce_product_variation');
    $this->logger = $logger;
    $this->config = $config;
    $this->orderTypeResolver = $order_type_resolver;
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->currentStore = $current_store;
    $this->currentUser = $current_user;
    $this->availabilityManager = $availability_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('direct_checkout_by_url'),
      $container->get('config.factory')->get('direct_checkout_by_url.settings'),
      $container->get('commerce_order.chain_order_type_resolver'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_store.current_store'),
      $container->get('current_user'),
      $container->get('commerce.availability_manager')
    );
  }

  /**
   * Builds the response.
   */
  public function build(Request $request) {
    // We allow two different variations of request. One is to pass a comma
    // separated list of SKUs in the products parameter. The other one is the
    // pass an array of products in the query parameters, in which case one
    // can specify the quantity as well.
    // An example on the first case:
    // direct-checkout-by-url?products=1234,5678
    // That would end up adding the products with sku 1234 and 5678 to the cart,
    // both with a quantity of 1.
    // An example one the second case:
    // direct-checkout-by-url?products[0][quantity]=2&products[0][sku]=1234
    // That would end up adding the product with sku 1234 to the cart with a
    // quantity of 2.
    // The very first case to handle, though, is if the request does not contain
    // a request key at all.
    if (!$request->get('products')) {
      throw new NotFoundHttpException();
    }
    $products = $request->get('products');
    if (is_string($products)) {
      return $this->handleProductsAsString($products);
    }
    if (is_array($products)) {
      return $this->handleProductsAsArray($products);
    }
    // No idea what else it could be, but that request would for sure be bad.
    throw new BadRequestHttpException();
  }

  /**
   * Helper to handle the string case.
   */
  protected function handleProductsAsString($products) {
    $products = explode(',', $products);
    $skus_with_quantities = [];
    foreach ($products as $product) {
      $skus_with_quantities[] = [
        'sku' => $product,
        'quantity' => 1,
      ];
    }
    return $this->handleProductsAsArray($skus_with_quantities);
  }

  /**
   * Helper to handle the array case.
   */
  protected function handleProductsAsArray(array $products) {
    $allow_unknown_skus = $this->config->get('allow_unknown_skus');
    $cart = NULL;
    foreach ($products as $product) {
      // This array should follow a specific pattern.
      if (!isset($product['sku']) || !isset($product['quantity'])) {
        continue;
      }
      // Try to load this product.
      $variation = $this->variationStorage->loadBySku($product['sku']);
      if (!$variation && !$allow_unknown_skus) {
        throw new NotFoundHttpException();
      }
      if (!$variation) {
        continue;
      }
      $store = $this->selectStore($variation);
      // Now see if it is at all possible to add it. Seems like this should be
      // unnecessary, so we have this issue opened for it:
      // https://www.drupal.org/project/commerce/issues/3023417.
      // If that ever gets fixed, this part should be removed.
      $context = new Context($this->currentUser, $store);
      if (!$this->availabilityManager->check($variation, $product['quantity'], $context)) {
        continue;
      }
      $cart = $this->cartProvider->getCart('default', $store, $this->currentUser);
      if (!$cart) {
        $cart = $this->cartProvider->createCart('default', $store, $this->currentUser);
      }
      // Reset the cart, if necessary.
      if ($this->config->get('reset_cart')) {
        $this->cartManager->emptyCart($cart);
      }
      $this->cartManager->addEntity($cart, $variation, $product['quantity']);
    }
    if (!$cart) {
      return $this->redirect('commerce_cart.page');
    }
    // Now redirect to the checkout page.
    return $this->redirect('commerce_checkout.form', [
      'commerce_order' => $cart->id(),
    ]);
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
