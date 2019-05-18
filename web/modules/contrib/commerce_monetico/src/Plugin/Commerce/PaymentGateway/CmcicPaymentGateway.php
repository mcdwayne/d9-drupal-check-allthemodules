<?php

namespace Drupal\commerce_cmcic\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the PaymentStandard payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_cmcic",
 *   label = "CM-CIC",
 *   display_label = "CM-CIC",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_cmcic\PluginForm\CmcicPaymentForm",
 *   },
 *   payment_method_types = {"credit_card"},
 * )
 */
class CmcicPaymentGateway extends OffsitePaymentGatewayBase {

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Version number'),
      '#description' => $this->t('The number of the version of the payment kit.'),
      '#default_value' => isset($this->configuration['version']) ? $this->configuration['version'] : '3.0',
      '#required' => TRUE,
    ];

    $form['tpe'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TPE number'),
      '#description' => $this->t('The TPE number of your CM-CIC account on 7 characters (eg. 1234567).'),
      '#default_value' => $this->configuration['tpe'],
      '#required' => TRUE,
    ];

    $form['company'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company number'),
      '#description' => $this->t('The company number of your CM-CIC account.'),
      '#default_value' => $this->configuration['company'],
      '#required' => TRUE,
    ];

    $form['security_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Security key'),
      '#description' => $this->t('The security key based on 40 characters.'),
      '#default_value' => $this->configuration['security_key'],
      '#required' => TRUE,
    ];

    $form['bank_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Bank type'),
      '#description' => $this->t('The bank type. This will define the URLs.'),
      '#options' => array(
        'cm' => $this->t('Crédit Mutuel'),
        'cic' => $this->t('CIC'),
        'obc' => $this->t('OBC'),
        'monetico' => $this->t('Monetico')
      ),
      '#default_value' => $this->configuration['bank_type'],
      '#required' => TRUE,
    ];

    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['bank_type'] = $values['bank_type'];
      $this->configuration['version'] = $values['version'];
      $this->configuration['security_key'] = $values['security_key'];
      $this->configuration['tpe'] = $values['tpe'];
      $this->configuration['company'] = $values['company'];
    }
  }
}
