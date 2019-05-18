<?php

namespace Drupal\uc_payumoney\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payumoney\Plugin\Ubercart\PaymentMethod\PayuMoneyRedirect;

/**
 * Returns responses for PayuMoney routes.
 */
class PayuMoneyController extends ControllerBase {

  /**
   * Handles a complete PayuMoney Payments Standard sale.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the cart or checkout complete page.
   */
  public function PayuComplete() {
    $order_id = $_POST['txnid'];
    \Drupal::entityManager()->getStorage('uc_order')->resetCache([$order_id]);
    $order = Order::load($order_id);
    switch ($_POST['status']) {
      case 'success':
        $comment = $this->t('PayuMoney mihpayid : @txn_id', ['@txn_id' => $_POST['mihpayid']]);
        uc_payment_enter($order_id, 'uc_payumoney', $amount, $order->getOwnerId(), NULL, $comment);
        uc_order_comment_save($order_id, 0, $this->t('PayuMoney reported a payment of @amount', ['@amount' => uc_currency_format($_POST['amount'], FALSE),]));
        break;

      case 'failure':
        uc_order_comment_save($order_id, 0, $this->t("The customer's attempted payment from a bank account failed."), 'admin');
        return $this->redirect('uc_cart.cart');
        break;
    }
    $session = \Drupal::service('session');
    $session->set('uc_checkout_complete_' . $order->id(), TRUE);
    return $this->redirect('uc_cart.checkout_complete');
  }

}