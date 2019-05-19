<?php

namespace Drupal\syncart\Controller;

use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_cart\CartProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\syncart\Service\SynCartServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CartController.
 */
class CartController extends ControllerBase {

  const CART_ROUTE = 'syncart.cart_controller_cart';

  /**
   * Current cart.
   *
   * @var \Drupal\syncart\Service\SynCartServiceInterface
   */
  protected $cart;

  /**
   * Constructs a new CartController object.
   *
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   Cart provider.
   * @param \Drupal\syncart\Service\SynCartServiceInterface $syncart
   *   Syncart cart.
   */
  public function __construct(
    CartProviderInterface $cart_provider,
    SynCartServiceInterface $syncart
  ) {
    $this->cart = new $syncart($cart_provider);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_provider'),
      $container->get('syncart.cart')
    );
  }

  /**
   * Cart.
   */
  public function cart() {
    return [
      '#theme' => 'syncart-cart',
      '#data' => [
        'cart' => $this->cart->renderCartPageInfo(),
      ],
    ];
  }

  /**
   * Cart.
   */
  public function checkout() {
    if ($this->cart->isEmpty()) {
      return $this->redirect('syncart.cart_controller_cart');
    }
    $cart = $this->cart->load();
    $url = Url::fromRoute('commerce_checkout.form', ['commerce_order' => $cart->id(), 'step' => 'order_information']);
    return new RedirectResponse($url->toString());
  }

  /**
   * Add product variation to cart.
   */
  public function addToCart() {
    $vid = \Drupal::request()->get('vid');
    $cart = $this->cart->addItem($vid);
    return new JsonResponse($this->cart->renderCartPageInfo());
  }

  /**
   * Add variations to cart.
   */
  public function addToCartMultiple() {
    $vids = Json::decode(\Drupal::request()->get('vids'));
    foreach ($vids as $vid) {
      $this->cart->addItem($vid, 1);
    }
    return new JsonResponse($this->cart->renderCartPageInfo());
  }

  /**
   * Задать количество товару в корзине.
   */
  public function setProductQuantity($vid) {
    $quantity = \Drupal::request()->get('quantity', 1);
    $this->cart->setOrderItemQuantity($vid, $quantity);
    return new JsonResponse($this->cart->renderCartPageInfo());
  }

  /**
   * Задать количество товару в корзине.
   */
  public function removeFromCart($vid) {
    $this->cart->removeOrderItem($vid);
    return $this->redirect(self::CART_ROUTE);
  }

  /**
   * Получение текущей корзины.
   */
  public function loadCart() {
    return new JsonResponse($this->cart->renderCartPageInfo());
  }

}
