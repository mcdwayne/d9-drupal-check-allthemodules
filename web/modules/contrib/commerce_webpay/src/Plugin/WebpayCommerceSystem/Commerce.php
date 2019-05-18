<?php

namespace Drupal\commerce_webpay\Plugin\WebpayCommerceSystem;

use Drupal\webpay\Plugin\WebpayCommerceSystemBase;
use Drupal\webpay\Entity\WebpayConfigInterface;
use Drupal\webpay\Entity\WebpayTransactionInterface;
use Drupal\commerce_payment\Entity\Payment;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;


/**
 * The commerce system of the webpay.
 *
 * @WebpayCommerceSystem(
 *   id = "commerce",
 *   label = @Translation("Commerce")
 * )
 */
class Commerce extends WebpayCommerceSystemBase {

  /**
   * {@inheritdoc}
   */
  public function transactionAccepted(WebpayConfigInterface $webpay_config, WebpayTransactionInterface $transaction) {
    $session_id = $transaction->get('session_id')->value;

    if ($payment = Payment::load($session_id)) {
      $payment->set('state', 'completed');
      $payment->set('remote_id', $transaction->id());
      $payment->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function transactionRejected(WebpayConfigInterface $webpay_config, WebpayTransactionInterface $transaction) {
    $session_id = $transaction->get('session_id')->value;

    if ($payment = Payment::load($session_id)) {
      $payment->set('state', 'voided');
      $payment->set('remote_id', $transaction->id());
      $payment->save();

      $return = Url::fromRoute('commerce_payment.checkout.return', [
        'commerce_order' => $payment->getOrderId(),
        'step' => 'payment',
      ], ['absolute' => TRUE]);

      return new RedirectResponse($return->toString());
    }

    return parent::transactionRejected($webpay_config, $transaction);
  }
}
