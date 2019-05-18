<?php

namespace Drupal\commerce_mollie\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Starts payment with the checkout form pane.
 */
class MolliePaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_mollie\Plugin\Commerce\PaymentGateway\Mollie $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $mollie_return = Url::fromRoute('commerce_mollie.checkout.mollie_return', [
      'commerce_order' => $payment->getOrder()->id(),
    ], ['absolute' => TRUE])->toString();

    $data = [
      'return' => $mollie_return,
      'cancel' => $form['#cancel_url'],
      'total' => $payment->getAmount()->getNumber(),
    ];

    $mollie_payment = $payment_gateway_plugin->createRemotePayment($payment, $data);

    return $this->buildRedirectForm($form, $form_state, $mollie_payment->getCheckoutUrl(), $data, 'get');

  }

}
