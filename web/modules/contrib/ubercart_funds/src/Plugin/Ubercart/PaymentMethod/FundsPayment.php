<?php

namespace Drupal\ubercart_funds\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\Core\Url;
use Drupal\ubercart_funds\Entity\Transaction;

/**
 * Defines a funds account payment method.
 *
 * Allows user to pay with their balance account.
 *
 * @UbercartPaymentMethod(
 *   id = "funds",
 *   name = @Translation("Funds account")
 * )
 */
class FundsPayment extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    return [
      '#markup' => $this->t('Funds - Pay with your balance account.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(OrderInterface $order) {
    $amount = $order->getTotal();
    $balance = \Drupal::service('ubercart_funds.transaction_manager')->loadAccountBalance(\Drupal::currentUser());

    if ($amount > ($balance / 100)) {
      $message = $this->t('You don\'t have enough funds to cover this payment. Please <a href="@deposit_link">deposit funds</a> first.', [
        '@deposit_link' => Url::fromRoute('uc_funds.deposit')->toString(),
      ]);

      return $message;
    }

    if (\Drupal::currentUser()->id() == 1) {
      $message = $this->t('Administrator are not allowed to buy product on their own website.');

      return $message;
    }

    $payment_method = $order->getPaymentMethodId();

    $transaction = Transaction::create([
      'issuer' => \Drupal::currentUser()->id(),
      'recipient' => 1,
      'type' => 'payment',
      'method' => $payment_method,
      'brut_amount' => intval($amount * 100),
      'net_amount' => intval($amount * 100),
      'fee' => 0,
      'currency' => $order->getCurrency(),
      'status' => 'Completed',
      'notes' => $this->t('Payment of order <a href="admin/store/orders//@order">@order</a> for an amount of @amount (@currency)', [
        '@order' => $order->Id(),
        '@amount' => $amount,
        '@currency' => $order->getCurrency(),
      ]),
    ]);
    $transaction->save();

    \Drupal::service('ubercart_funds.transaction_manager')->performTransaction($transaction);

    uc_payment_enter($order->id(), 'funds', 0, 0, NULL, $this->t('Checkout completed with account funds.'));
  }

}
