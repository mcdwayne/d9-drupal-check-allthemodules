<?php

namespace Drupal\commerce_payscz\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * Pays.cz production gateway URL.
   */
  const PAYSCZ_URL = 'https://www.pays.cz/paymentorder';

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->plugin->getConfiguration();

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->getEntity();
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $data = [
      'Merchant' => $configuration['merchant_id'],
      'Shop' => $configuration['shop_id'],
      'Currency' => $payment->getAmount()->getCurrencyCode(),
      'Amount' => (int) $payment->getAmount()->getNumber() * 100,
      'MerchantOrderNumber' => $configuration['merchant_order_number_prefix'] . $payment->getOrderId(),
      'Email' => $configuration['send_email'] ? $payment->getOrder()->getEmail() : '',
    ];

    $form = $this->buildRedirectForm($form, $form_state, self::PAYSCZ_URL, $data, self::REDIRECT_GET);

    return $form;
  }

}
