<?php

namespace Drupal\commerce_datatrans\PluginForm;

use Drupal\commerce_datatrans\DatatransHelper;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_price\Entity\Currency;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a checkout form for the Datatrans gateway.
 */
class DatatransForm extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $gateway = $payment->getPaymentGateway()->getPlugin();
    $gateway_config = $gateway->getConfiguration();
    $order = $payment->getOrder();

    $currency_code = $payment->getAmount()->getCurrencyCode();
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
    $currency = Currency::load($currency_code);

    // Calculate the amount in the form Datatrans expects it.
    $amount = intval($order->getTotalPrice()->getNumber() * pow(10, $currency->getFractionDigits()));

    $data = [
      'merchantId' => $gateway_config['merchant_id'],
      'amount' => $amount,
      'refno' => $order->id(),
      'sign' => NULL,
      'currency' => $currency_code,
      'successUrl' => $form['#return_url'],
      'errorUrl' => $form['#return_url'],
      'cancelUrl' => $form['#cancel_url'],
      'security_level' => $gateway_config['security_level'],
    ];

    // Request type.
    if (!empty($gateway_config['req_type']) && $gateway_config['req_type'] != 'ignore') {
      $payment_data['reqtype'] = $gateway_config['req_type'];
    }

    // Handle security levels.
    switch ($gateway_config['security_level']) {
      case 1:
        $data['sign'] = $gateway_config['sign'];
        break;

      case 2:
        // Generates the sign.
        $data['sign'] = DatatransHelper::generateSign($gateway_config['hmac_key'], $gateway_config['merchant_id'], $amount, $currency_code, $order->id());
        break;
    }

    // If use alias option was enabled in method configuration apply this for
    // this payment method plugin.
    if ($gateway_config['use_alias']) {
      $data['useAlias'] = 'true';
    }

    return $this->buildRedirectForm($form, $form_state, $gateway_config['service_url'], $data, static::REDIRECT_POST);
  }

}
