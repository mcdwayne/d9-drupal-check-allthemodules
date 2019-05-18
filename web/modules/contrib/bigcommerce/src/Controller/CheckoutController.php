<?php

namespace Drupal\bigcommerce\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\CartSessionInterface;
use Drupal\commerce_cart\CartSession;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\JsonResponse;


use BigCommerce\Api\v3\ApiClient;
use BigCommerce\Api\v3\Api\CartApi;
use Drupal\bigcommerce\ClientFactory;

/**
 * Provides embedded BigCommerce checkout.
 */
class CheckoutController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The BigCommerce API settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The cart session.
   *
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  protected $cartSession;

  /**
   * Constructs a new CartEventSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CartProviderInterface $cartProvider, CartSessionInterface $cartSession) {
    $this->config = $config_factory->get('bigcommerce.settings');
    $this->cartProvider = $cartProvider;
    $this->cartSession = $cartSession;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_cart.cart_session')
    );
  }

  /**
   * Loads BigCommerce embedded checkout.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   Renderable array
   */
  public function content(RouteMatchInterface $route_match) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');

    $page = [
      '#type' => 'container',
      // '#theme' => 'bigcommerce_checkout',
      '#attached' => [
        'library' => [
          'bigcommerce/checkout',
        ],
      ],
      '#attributes' => [
        'id' => [
          'bigcommerce-checkout-container',
        ],
      ],
    ];

    try {
      $base_client = new ApiClient(ClientFactory::createApiConfiguration($this->config->get('api')));
      $cart_api = new CartApi($base_client);

      // The API lists both passing the channel id and adding it as a parameter.
      $response = $cart_api->cartsCartIdRedirectUrlsPost($order->getData('bigcommerce_cart_id'));

      // Urls are one-time use, so we don't bother saving them to the order.
      $urls = $response->getData();

    }
    catch (\Exception $e) {

    }

    $page['#attached']['drupalSettings']['bigCommerceCheckoutUrl'] = $urls->getEmbeddedCheckoutUrl();
    $page['#attached']['drupalSettings']['bigCommerceOrderId'] = $order->id();

    return $page;
  }

  /**
   * Ajax callback to handle closing the cart after checkout completion.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function finalizeCart(RouteMatchInterface $route_match) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');

    $this->cartProvider->finalizeCart($order, FALSE);
    $order->getState()->applyTransitionById('place');
    $order->save();

    return new JsonResponse(['success' => TRUE]);
  }

  /**
   * Checks access for the form page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');
    if ($order->getState()->getId() != 'draft') {
      return AccessResult::forbidden()->addCacheableDependency($order);
    }

    // The user can checkout only their own non-empty orders.
    if ($account->isAuthenticated()) {
      $customer_check = $account->id() == $order->getCustomerId();
    }
    else {
      $customer_check = $this->cartSession->hasCartId($order->id(), CartSession::ACTIVE);
    }

    $access = AccessResult::allowedIf($customer_check)
      ->andIf(AccessResult::allowedIf($order->hasItems()))
      ->andIf(AccessResult::allowedIfHasPermission($account, 'access checkout'))
      ->addCacheableDependency($order);

    return $access;
  }

}
