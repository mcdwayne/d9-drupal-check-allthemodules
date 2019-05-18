<?php

namespace Drupal\commerce_worldline\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_worldline\ValidateExternalPayment;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the SIPS Payment for Offsite purchases.
 *
 * @CommercePaymentGateway(
 *   id = "sips_payment",
 *   label = "SIPS Payment (Offsite)",
 *   display_label = "SIPS Payment",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_worldline\PluginForm\OffsiteRedirect\SIPSPaymentRedirectForm",
 *   },
 *   payment_method_types = {"sips"},
 *   modes = {"TEST", "SIMU", "PRODUCTION"}
 * )
 */
class SIPSPaymentGateway extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel() {
    return $this->configuration['display_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'sips_interface_version' => '',
      'sips_passphrase' => '',
      'sips_merchant_id' => '',
      'sips_key_version' => '',
      'sips_payment_method' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['sips_interface_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Interface version'),
      '#default_value' => $this->configuration['sips_interface_version'],
      '#required' => TRUE,
    ];

    $form['sips_passphrase'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Passphrase'),
      '#default_value' => $this->configuration['sips_passphrase'],
      '#required' => TRUE,
    ];

    $form['sips_merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#default_value' => $this->configuration['sips_merchant_id'],
      '#required' => TRUE,
    ];

    $form['sips_key_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key version'),
      '#default_value' => $this->configuration['sips_key_version'],
      '#required' => TRUE,
    ];

    $form['sips_payment_method'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payment method'),
      '#description' => $this->t('Leave empty for selection at SIPS, can be filled in with specific methods, such as VISA.'),
      '#default_value' => $this->configuration['sips_payment_method'],
      '#required' => FALSE,
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
      $this->configuration['sips_interface_version'] = $values['sips_interface_version'];
      $this->configuration['sips_passphrase'] = $values['sips_passphrase'];
      $this->configuration['sips_merchant_id'] = $values['sips_merchant_id'];
      $this->configuration['sips_key_version'] = $values['sips_key_version'];
      $this->configuration['sips_payment_method'] = $values['sips_payment_method'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    parent::onReturn($order, $request);

    /* @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $order->payment_gateway->entity;
    $config = $payment_gateway->getPlugin()->getConfiguration();

    $vep = new ValidateExternalPayment($this->entityTypeManager, $config);
    $vep->validateRequest($request, $order);
  }

}
