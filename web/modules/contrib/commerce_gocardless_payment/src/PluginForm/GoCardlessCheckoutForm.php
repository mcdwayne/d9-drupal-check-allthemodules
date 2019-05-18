<?php

namespace Drupal\commerce_gocardless_payment\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GoCardlessCheckoutForm.
 *
 * @package Drupal\commerce_gocardless_payment\PluginForm
 */
class GoCardlessCheckoutForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_gocardless_payment\Plugin\Commerce\PaymentGateway\GoCardlessCheckoutInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $extra = [
      'return_url' => $form['#return_url'],
      'cancel_url' => $form['#cancel_url'],
      'capture' => $form['#capture'],
    ];
    $gocardless_config = $payment_gateway_plugin->getConfiguration();

    $order = $payment->getOrder();
    $order->setData('gocardless_checkout', [
      'flow' => 'ec',
      'access_token' => $gocardless_config['access_token'],
      'payerid' => FALSE,
      'capture' => $extra['capture'],
    ]);
    $order->save();

    $data = [
      'access_token' => $gocardless_config['access_token'],
      'return' => $form['#return_url'],
      'cancel' => $form['#cancel_url'],
      'total' => $payment->getAmount()->getNumber(),
    ];

    return $this->buildRedirectForm($form, $form_state, $payment_gateway_plugin->getRedirectUrl($payment), $data, 'get');
  }

}
