<?php

namespace Drupal\commerce_satispay\PluginForm;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class SatispayOnlineForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_satispay\Plugin\Commerce\PaymentGateway\SatispayOnline $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $extra = [
      'return_url' => $form['#return_url'],
      'cancel_url' => $form['#cancel_url'],
      'capture' => $form['#capture'],
    ];

    $satispay_response = $payment_gateway_plugin->setCheckout($payment, $extra);

    // Debug.
    \Drupal::logger('commerce_satispay')->notice('Checkout: @checkout', ['@checkout' => json_encode($satispay_response)]);

    // If we didn't get a checkout id back from Satispay, then
    // we need to exit checkout.
    if (!isset($satispay_response->id)) {
      throw new PaymentGatewayException(sprintf('[Satispay error #%s]: %s', $satispay_response->code, $satispay_response->message));
    }

    $order = $payment->getOrder();
    $order->setData('satispay', [
      'uuid' => $satispay_response->id,
    ]);
    $order->save();

    // Update the remote id for the captured transaction.
    $payment->setRemoteId($satispay_response->id);
    $payment->save();

    $data = [
      'uuid' => $satispay_response->id,
      'return' => $form['#return_url'],
      'cancel' => $form['#cancel_url'],
      'total' => $payment->getAmount()->getNumber(),
    ];

    $redirect_url = $satispay_response->checkout_url;

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, 'get');
  }

}
