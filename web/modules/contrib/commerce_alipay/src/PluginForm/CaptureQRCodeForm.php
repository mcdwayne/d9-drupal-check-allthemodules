<?php

namespace Drupal\commerce_alipay\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class CaptureQRCodeForm extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['barcode'] = [
      '#type' => 'number',
      '#title' => t('Barcode'),
      '#size' => 18
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $order    = $payment->getOrder();
    $price    = $payment->getAmount();
    $store_id = $order->getStoreId();

    try {
      $payment_gateway_plugin->capture((string) $order->id(), $values['barcode'], $price, $store_id);

    } catch (\Exception $e) {
       // Payment is not successful
      \Drupal::logger('commerce_alipay')->error($e->getMessage());
      $form_state->setError($form['barcode'], t('Commerce Alipay is having problem to connect to Alipay servers: ') . $e->getMessage());
    }
  }
}
