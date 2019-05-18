<?php

namespace Drupal\commerce_payeezy\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_payeezy_hosted_gateway",
 *   label = "Payeezy hosted gateway",
 *   display_label = "Payeezy hosted gateway",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_payeezy\PluginForm\HostedGateway\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class HostedGateway extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'x_login' => '',
      'transaction_key' => '',
      'x_response_key' => '',
      'transaction_url' => '',
      'hmac_calculation' => 'md5',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['x_login'] = [
      '#type' => 'textfield',
      '#title' => t('Login (x_login)'),
      '#required' => TRUE,
      '#description' => $this->t('You can get this from Hash Calculator settings for a Payment Page ID under Payment Pages tab'),
      '#default_value' => $this->configuration['x_login'],
    ];
    $form['transaction_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Transaction Key'),
      '#description' => $this->t('You can get this from Hash Calculator settings for a Payment Page ID under Payment Pages tab'),
      '#default_value' => $this->configuration['transaction_key'],
    ];
    $form['x_response_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Response key'),
      '#description' => $this->t('You can get this from Security settings for a Payment Page ID under Payment Pages tab'),
      '#default_value' => $this->configuration['x_response_key'],
    ];
    $form['transaction_url'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Transaction URL'),
      '#description' => $this->t('Usually this is https://demo.globalgatewaye4.firstdata.com/pay'),
      '#default_value' => $this->configuration['transaction_url'],
    ];
    $form['hmac_calculation'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('HMAC Calculation'),
      '#options' => [
        'md5' => 'MD5',
        'sha1' => 'SHA-1',
      ],
      '#description' => $this->t('Set this value as the same you set for Encrytion Type under Security setting for a Payment Page ID.'),
      '#default_value' => $this->configuration['hmac_calculation'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['x_login'] = $values['x_login'];
      $this->configuration['transaction_key'] = $values['transaction_key'];
      $this->configuration['x_response_key'] = $values['x_response_key'];
      $this->configuration['transaction_url'] = $values['transaction_url'];
      $this->configuration['hmac_calculation'] = $values['hmac_calculation'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $payment_status = $request->get('x_response_reason_text');

    if ($request->get('x_response_code') == 1) {
      $response_key = $this->configuration['x_response_key'];
      $x_login = $this->configuration['x_login'];
      $x_trans_id = $request->get('x_trans_id');
      $x_amount = $request->get('x_amount');

      // Calculate HMAC hash.
      $algo = $this->configuration['hmac_calculation'];
      $calculated_hmac = $response_key . $x_login . $x_trans_id . $x_amount;
      $calculated_hmac = hash($algo, $calculated_hmac);

      // Get HMAC hash from payload.
      $algo = ($algo == 'sha1') ? 'x_SHA1_Hash' : 'x_MD5_Hash';
      $payeezy_hmac = $request->get($algo);

      if ($calculated_hmac == $payeezy_hmac) {
        $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
        $payment = $payment_storage->create([
          'state' => 'authorization',
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $this->entityId,
          'order_id' => $order->id(),
          'test' => $this->getMode() == 'test',
          'remote_id' => $x_trans_id,
          'remote_state' => $payment_status,
          'authorized' => \Drupal::time()->getRequestTime(),
        ]);
        $payment->save();

        drupal_set_message($this->t('Your payment was successful with Order ID : @orderid and Transaction ID : @transaction_id', [
          '@orderid' => $order->id(),
          '@transaction_id' => $x_trans_id,
        ]));
      }
      else {
        drupal_set_message($this->t('Payment was not processed. Invalid transaction.'));
      }
    }
    else {
      drupal_set_message($payment_status);
      throw new PaymentGatewayException();
    }
  }

}
