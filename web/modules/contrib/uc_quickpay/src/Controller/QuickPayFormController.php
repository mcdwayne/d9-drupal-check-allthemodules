<?php

namespace Drupal\uc_quickpay\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_payment\Plugin\PaymentMethodManager;
use Drupal\uc_quickpay\Plugin\Ubercart\PaymentMethod\QuickPayPaymentForm;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns response for QuickPay Form Payment Method.
 */
class QuickPayFormController extends ControllerBase {

  /**
   * The payment method manager.
   *
   * @var \Drupal\uc_payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructs a QuickPayFormController.
   *
   * @param \Drupal\uc_payment\Plugin\PaymentMethodManager $payment_method_manager
   *   The payment method.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(PaymentMethodManager $payment_method_manager, SessionInterface $session) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_payment.method'),
      $container->get('session')
    );
  }

  /**
   * Quickpay complete request.
   *
   * @todo Handles a complete QuickPay Payments request.
   */
  public function quickpayComplete() {
    // Checking current session current order.
    if (!$this->session->has('cart_order') || !($order = Order::load($this->session->get('cart_order')))) {
      drupal_set_message($this->t('Thank you for your order! Quickpay form payment will notify you once your payment has been processed.'));
      return $this->redirect('uc_cart.cart');
    }
    // Checking is that payment method is QuickPay Form.
    $method = $this->paymentMethodManager->createFromOrder($order);
    if (!$method instanceof QuickPayPaymentForm) {
      return $this->redirect('uc_cart.cart');
    }
    // This lets us know it's a legitimate access of the complete page.
    $message = $this->t('Quickpay form payment has been successful of amount : @amount.',
      [
        '@amount' => uc_currency_format($order->getTotal()),
      ]
    );
    // Comment order.
    uc_order_comment_save($order->id(), $order->getOwnerId(), $message, 'admin');
    // Update order status.
    $order->setStatusId('payment_received')->save();
    $this->session->set('uc_checkout_complete_' . $order->id(), TRUE);
    return $this->redirect('uc_cart.checkout_complete');
  }

  /**
   * Quickpay cancel request.
   *
   * @todo Handles a cancel QuickPay Payments request.
   */
  public function quickpayCancel() {
    $order = Order::load($this->session->get('cart_order'));
    // Checking is that payment method is QuickPay Form.
    $method = $this->paymentMethodManager->createFromOrder($order);
    if (!$method instanceof QuickPayPaymentForm) {
      return $this->redirect('uc_cart.cart');
    }
    // Checking current session current order.
    $message = $this->t('Quickpay form payment has been cancelled occurred some unnecessary action: @amount.',
      [
        '@amount' => uc_currency_format($order->getTotal()),
      ]
    );
    // Comment order.
    uc_order_comment_save($order->id(), $order->getOwnerId(), $message, 'admin');
    // Update order status.
    $order->setStatusId('canceled')->save();
    // Remove session for the current order.
    $this->session->remove('cart_order');
    drupal_set_message($this->t('Quickpay form payment has been cancelled occurred some unnecessary action. Please review your cart and try again.'));
    return $this->redirect('uc_cart.cart');
  }

}
