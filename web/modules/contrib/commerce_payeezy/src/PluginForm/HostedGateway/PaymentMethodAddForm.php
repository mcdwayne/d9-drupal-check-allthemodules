<?php

namespace Drupal\commerce_payeezy\PluginForm\HostedGateway;

use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Payment form for Hosted Gateway method.
 */
class PaymentMethodAddForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $payment = $this->entity;
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $config = $payment_gateway_plugin->getConfiguration();

    // Prepare sign off variables.
    $amount = $payment->getAmount()->getNumber();

    if ($amount > 0) {
      $x_login = $config['x_login'];
      $transaction_key = $config['transaction_key'];
      $x_amount = number_format($amount, 2);
      $x_currency_code = $payment->getAmount()->getCurrencyCode();
      $x_fp_sequence = rand(1000, 100000) + 123456;
      $x_fp_timestamp = time();

      // Generate HMAC based on selected algorithm.
      $algo = $config['hmac_calculation'];
      $hmac_data = $x_login . '^' . $x_fp_sequence . '^' . $x_fp_timestamp . '^' . $x_amount . '^' . $x_currency_code;
      $x_fp_hash = hash_hmac($algo, $hmac_data, $transaction_key);
      $redirect_url = $config['transaction_url'];

      $data = [
        'x_receipt_link_url' => $form['#return_url'],
        'x_receipt_link_method' => 'POST',
        'x_receipt_link_text' => t('Return to @sitename', ['@sitename' => \Drupal::token()->replace('[site:name]')]),
        'cancel' => $form['#cancel_url'],
        'x_invoice_num' => t('Order Number: @order_number', ['@order_number' => $payment->getOrderId()]),
        'x_login' => $x_login,
        'x_amount' => $x_amount,
        'x_fp_sequence' => $x_fp_sequence,
        'x_fp_timestamp' => $x_fp_timestamp,
        'x_fp_hash' => $x_fp_hash,
        'x_currency_code' => $x_currency_code,
        'x_show_form' => 'PAYMENT_FORM',
        'x_relay_response' => 'TRUE',
      ];

      return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, 'post');
    }
    else {
      throw new HardDeclineException(t('The payment was declined'));
    }
  }

}
