<?php

namespace Drupal\commerce_braintree_marketplace\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentGatewayFormBase;
use Drupal\Core\Form\FormStateInterface;

class PaymentEscrowReleaseForm extends PaymentGatewayFormBase {

  /**
   * @inheritDoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#success_message'] = t('Marketplace payment escrow released.');
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_braintree_marketplace\Plugin\Commerce\PaymentGateway\MarketplaceInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->releaseFromEscrow($payment);
  }

}
