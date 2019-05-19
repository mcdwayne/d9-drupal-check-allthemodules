<?php

/**
 * @file
 * Contains \Drupal\uc_everyday\Controller\EverydayController.
 */

namespace Drupal\uc_everyday\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\Entity\Order;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\uc_cart\CartManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Controller routines for uc_everyday.
 */
class EverydayController extends ControllerBase {
  /**
   * The cart manager.
   *
   * @var \Drupal\uc_cart\CartManager
   */
  protected $cartManager;

  /**
   * Constructs a EverydayCheckoutController.
   *
   * @param \Drupal\uc_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   */
  public function __construct(CartManagerInterface $cart_manager) {
    $this->cartManager = $cart_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // @todo: Also need to inject logger
    return new static(
      $container->get('uc_cart.manager')
    );
  }

  /**
   * Finalizes Everyday transaction.
   *
   * @param int $cart_id
   *   The cart identifier.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   */
  public function complete($cart_id = 0, Request $request) {
    $cart_config = \Drupal::config('uc_cart.settings');
    $module_config = \Drupal::config('uc_everyday.settings');

    \Drupal::logger('uc_everyday')->notice('Receiving new order notification for order @order_id.', ['@order_id' => SafeMarkup::checkPlain($request->query->get('order_id'))]);

    $order = Order::load($request->query->get('order_id'));

    if ($order === FALSE || $order->getStateId() != 'in_checkout' || $this::hash_check($request) != $request->query->get('OPR_RETURN_MAC')) {
      \Drupal::logger('uc_everyday')
        ->notice('/cart/everyday/complete attempted for non-existent order.', array(), WATCHDOG_ERROR);

      return $this->t('An error has occurred during payment. Please contact us to ensure your order has submitted.');
    }

    // Save changes to order without it's completion - it will be on
    // finalization step. See http://drupal.org/node/1332130
    $order->save();

    if ($order) {
      // Define local variables.
      $opr_reference_nbr = $request->query->get('OPR_RETURN_PAID');

      // Add payment and comments.
      $comment = t('Everyday reference number: @txn_id', array('@txn_id' => $opr_reference_nbr));
      //uc_payment_enter($order->id(), 'everyday', number_format($order->getTotal(), 2), $order->uid(), NULL, $comment);
      uc_payment_enter($order->id(), 'everyday', number_format($order->getTotal(), 2), 0, NULL, $comment);
      uc_order_comment_save($order->id(), 0, $this->t('Payment of @amount @currency submitted through Everyday.',
        ['@amount' => number_format($order->getTotal(), 2), '@currency' => 'EUR']),
        'order', 'payment_received');
      uc_order_comment_save($order->id(), 0, $this->t('Everyday reported a payment of @amount @currency.',
        ['@amount' => number_format($order->getTotal(), 2), '@currency' => 'EUR']),
        'admin');
    }
    else {
      drupal_set_message($this->t('Errors in response parameters from Everday service has been detected. Please contact the site administrator.'));
    }

    // Add a comment to let sales team know this came in through the site.
    uc_order_comment_save($order->id(), 0, $this->t('Order created through Everyday Online Payment website.'), 'admin');

    // Empty that cart...
    $cart = $this->cartManager->get($cart_id);
    $cart->emptyCart();

    // Add a comment to let sales team know this came in through the site.
    uc_order_comment_save($order->id(), 0, $this->t('Order created through website.'), 'admin');

    $build = $this->cartManager->completeSale($order, $cart_config->get('new_customer_login'));

    return $build;
  }

  /**
   * Handles a cancelled payment from Everyday.
   */
  public function cancel() {
    drupal_set_message($this->t('Payment through Everyday Online Payment service has been cancelled. Your order is not completed. Please try again or contact us.'), 'ERROR');
    return new TrustedRedirectResponse('/cart/checkout');
  }

  /**
   * Handles an error in payment from Everyday.
   */
  public function reject() {
    drupal_set_message($this->t('Payment has been rejected by Everyday Online Payment service. Please select another payment method to complete your order.'), 'ERROR');
    return new TrustedRedirectResponse('/cart/checkout');
  }

  /**
   * Calculate hash based on returned values - checked against returned hash.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   */
  private function hash_check(Request $request) {
    $module_config = \Drupal::config('uc_everyday.settings');

    // Validate response fields with hash.
    $hash_elements = array(
      'OPR_RETURN_VERSION',
      'OPR_RETURN_STAMP',
      'OPR_RETURN_REF',
      'OPR_RETURN_PAID',
    );

    foreach ($hash_elements as $key => $element) {
      $hash_elements[$element] = $request->query->get($element);
      unset($hash_elements[$key]);
    }

    if ($module_config->get('uc_everyday_mode') == '1') {
      $everyday_secretkey = $module_config->get('uc_everyday_test_secret_key');
    }
    else {
      $everyday_secretkey = $module_config->get('uc_everyday_secret_key');
    }

    $hash_elements['secret_key'] = $everyday_secretkey;

    // Generate hash with md5, convert to uppercase.
    $hash = strtoupper(md5(implode('&', $hash_elements) . '&'));

    return $hash;
  }
}
