<?php

namespace Drupal\commerce_payone\PluginForm\SofortBanking;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class SofortBankingOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_payone\Plugin\Commerce\PaymentGateway\PayoneSofortInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;

    $sofort = $payment_gateway_plugin->initializeSofortApi($payment, $form);

    if ($sofort->status != 'REDIRECT') {
      // SOFORT-API didn't accept the data.
      throw new PaymentGatewayException(sprintf('SOFORT error: %s', $sofort->getError()));
    }

    $transaction_id = $sofort->txid;
    $payment->setRemoteId($transaction_id);
    $payment->state = 'authorization';
    $payment->save();

    $order = $payment->getOrder();
    $order->setData('sofort_gateway', [
      'transaction_id' => $transaction_id,
    ]);
    $order->save();

    return $this->buildRedirectForm($form, $form_state, $sofort->redirecturl, []);
  }

}
