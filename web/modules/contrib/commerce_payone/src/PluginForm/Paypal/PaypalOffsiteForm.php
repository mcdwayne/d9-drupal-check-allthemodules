<?php

namespace Drupal\commerce_payone\PluginForm\Paypal;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PaypalOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_payone\Plugin\Commerce\PaymentGateway\PayonePaypalInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;

    $paypal = $payment_gateway_plugin->initializePaypalApi($payment, $form);

    if ($paypal->status != 'REDIRECT') {
      throw new PaymentGatewayException(sprintf('Paypal error: %s', $paypal->getError()));
    }

    $transaction_id = $paypal->txid;
    $payment->setRemoteId($transaction_id);
    $payment->state = 'authorization';
    $payment->save();

    $order = $payment->getOrder();
    $order->setData('sofort_gateway', [
      'transaction_id' => $transaction_id,
    ]);
    $order->save();

    return $this->buildRedirectForm($form, $form_state, $paypal->redirecturl, []);
  }

}
