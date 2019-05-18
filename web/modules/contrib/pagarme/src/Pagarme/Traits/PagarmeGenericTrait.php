<?php
namespace Drupal\pagarme\Pagarme\Traits;
use Drupal\commerce_price\Price;
use Drupal\pagarme\Pagarme\PagarmePostback;

trait PagarmeGenericTrait {
  /**
   * {@inheritdoc}
   */
  public function createPayment($transaction, $order, $payment) {

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    $amount = strval($transaction->getAmount() / 100);
    $currency_code = $payment->getAmount()->getCurrencyCode();

    $payment = $payment_storage->create([
      'state' => $transaction->getStatus(),
      'amount' => new Price($amount, $currency_code),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'test' => $this->getMode() == 'test',
      'remote_id' => $transaction->getId(),
      'remote_state' => $transaction->getStatus(),
      'authorized' => \Drupal::time()->getRequestTime()
    ]);
    $payment->save();

    $pagarme_data = array(
      'pagarme_id' => $transaction->getId(),
      'payment_method' => $transaction->getPaymentMethod(),
      'amount' => $transaction->getAmount(),
      'payment_status' => $transaction->getStatus(),
      'order_id' => $order->id(),
      'consumer_email' => $order->getCustomer()->getEmail(),
    );
    $pagarmePostback = new PagarmePostback($pagarme_data, $order);
    $pagarmePostback->processPagarmeData();
  }
}