<?php

namespace Drupal\commerce_webpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\webpay\Entity\WebpayConfig;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Entity\Payment;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "webpay",
 *   label = "Webpay",
 *   display_label = "Webpay",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_webpay\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   payment_type = "payment_manual",
 * )
 */
class WebpayRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'webpay_config' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configurations = [];

    $storage_config = \Drupal::service('entity.manager')->getStorage('webpay_config');
    $configs = $storage_config->loadMultiple();
    foreach ($configs as $config) {
      $configurations[$config->id()] = $config->label();
    }
    $form['webpay_config'] = [
      '#type' => 'select',
      '#title' => $this->t('Webpay Config'),
      '#options' => $configurations,
      '#required' => TRUE,
      '#default_value' => $this->configuration['webpay_config'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['webpay_config'] = $values['webpay_config'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $token = $request->request->get('token_ws');

    if ($token && ($transaction = webpay_get_transaction_by_token($token))) {
      if ($payment = Payment::load($transaction->get('session_id')->value)) {
        if ($payment->get('state')->value == 'completed') {
          return;
        }
      }
    }

    throw new PaymentGatewayException($this->t('We encountered an unexpected error processing your payment method. Please try again later.'));
  }

}
