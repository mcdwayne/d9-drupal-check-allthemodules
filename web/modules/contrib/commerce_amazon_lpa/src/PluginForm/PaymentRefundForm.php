<?php

namespace Drupal\commerce_amazon_lpa\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentRefundForm as PaymentRefundFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_price\Price;

/**
 * The payment refund form.
 */
class PaymentRefundForm extends PaymentRefundFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['notes'] = [
      '#title' => t('Notes'),
      '#type' => 'textfield',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $amount = new Price($values['amount']['number'], $values['amount']['currency_code']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_amazon_lpa\Plugin\Commerce\PaymentGateway\AmazonPay $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->refundPaymentWithNotes($payment, $amount, $values['notes']);
  }

}
