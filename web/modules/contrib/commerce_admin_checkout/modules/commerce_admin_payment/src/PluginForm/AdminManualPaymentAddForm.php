<?php

namespace Drupal\commerce_admin_payment\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentGatewayFormBase;
use Drupal\Core\Form\FormStateInterface;

class AdminManualPaymentAddForm extends PaymentGatewayFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();
    if (!$order) {
      throw new \InvalidArgumentException('Payment entity with no order reference given to PaymentAddForm.');
    }

    $config = $this->entity->getPaymentGateway()->getPluginConfiguration();

    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Amount'),
      '#default_value' => NULL,
      '#required' => TRUE,
      '#allow_negative' => !empty($config['allow_negative']),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#rows' => 3,
    ];

    if (!empty($config['automatically_received'])) {
      $form['received'] = [
        '#type' => 'value',
        '#value' => TRUE,
      ];
    }
    else {
      $form['received'] = [
        '#type' => 'checkbox',
        '#title' => t('The specified amount was already received.'),
      ];
    }


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $payment->amount = $values['amount'];
    /** @var \Drupal\commerce_admin_payment\Plugin\Commerce\PaymentGateway\AdminManualPaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    if (!empty($values['description'])) {
      $payment->set('description', $values['description']);
    }
    $payment_gateway_plugin->createPayment($payment, $values['received']);
  }

}
