<?php

/**
 * Commerce repeat order.
 */
namespace Drupal\commerce_repeat_order\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_cart\CartManager;
use Drupal\commerce_cart\CartProvider;
use \Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\Product;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommerceRepeatOrder extends ControllerBase {

  /**
   * The current order.
   *
   * @var \Drupal\commerce_order\Entity\Order
   */
  protected $order;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManager
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProvider
   */
  protected $cartProvider;

  /**
   * CommerceRepeatOrder constructor.
   *
   * @param CartManager $cartManager
   * @param CartProvider $cartProvider
   */
  public function __construct(CartManager $cartManager, CartProvider $cartProvider) {
    $this->cartManager = $cartManager;
    $this->cartProvider = $cartProvider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('commerce_cart.cart_manager'),
        $container->get('commerce_cart.cart_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function repeatOrder(Order $commerce_order) {
    $this->order = $commerce_order;

    // The current user ID.
    $uid = $this->currentUser()->id();
    $order_uid = $this->order->getCustomerId();

    if (!empty($order_uid) && $uid == $order_uid) {
      // Loading config for module.
      $config = $this->config('commerce_repeat_order.settings');
      $add_or_override = $config->get('add_or_override');

      // Loading existing cart.
      $cart = $this->cartProvider->getCart('default', $this->order->getStore());
      if (empty($cart)) {
        // Creating new cart is there is none.
        $cart = $this->cartProvider->createCart('default', $this->order->getStore());
      }
      elseif ($add_or_override == 'override') {
        $this->cartManager->emptyCart($cart);
      }

      foreach ($this->order->getItems() as $order_item) {
        // Creating new duplicate order item for adding in cart.
        $purchase_order_id = $order_item->getPurchasedEntityId();
        $product = Product::load($purchase_order_id);
        if ($product->isPublished()) {
          $order_item_new = $order_item->createDuplicate();
          $order_item_new->enforceIsNew();
          $order_item_new->id = NULL;
          $order_item_new->order_item_id = NULL;
          $order_item_new->save();
          // Adding order item in cart.
          $this->cartManager->addOrderItem($cart, $order_item_new);
        }
        else {
          $message = "Some products weren't copied to the cart as they aren't currently available.";
        }
      }
      if (isset($message) && ($config->get('status_message') == 'show')) {
        drupal_set_message(t('@msg', array('@msg' => $message)), 'status', FALSE);
      }
    }
    else {
      drupal_set_message(t('You can only repeat your own order.'), 'error', FALSE);
    }
    return $this->redirect('commerce_cart.page');
  }

}
