<?php

namespace Drupal\uc_paypal_plus\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines the PayPal Express Checkout payment method.
 */
 //to-do replace Express with PayPal Plus to ensure there is no cross-module confusion
abstract class PayPalplusPaymentMethodPluginBase extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'wpsplus_email' => '',
      'wppplus_server' => 'https://api-3t.sandbox.paypal.com/nvp',
      'apiplus' => [
        'apiplus_username' => '',
        'apiplus_password' => '',
        'apiplus_signature' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['wpsplus_email'] = array(
      '#type' => 'email',
      '#title' => $this->t('PayPal e-mail address'),
      '#description' => $this->t('The e-mail address you use for the PayPal account you want to receive payments.'),
      '#default_value' => $this->configuration['wpsplus_email'],
    );
    $form['wppplus_server'] = array(
      '#type' => 'select',
      '#title' => $this->t('API server'),
      '#description' => $this->t('Sign up for and use a Sandbox account for testing.'),
      '#options' => array(
        'https://api-3t.sandbox.paypal.com/nvp' => $this->t('Sandbox'),
        'https://api-3t.paypal.com/nvp' => $this->t('Live'),
      ),
      '#default_value' => $this->configuration['wppplus_server'],
    );
    $form['apiplus'] = array(
      '#type' => 'details',
      '#title' => $this->t('API credentials'),
      '#description' => $this->t('@link for information on obtaining credentials. You need to acquire an API Signature. If you have already requested API credentials, you can review your settings under the API Access section of your PayPal profile.', ['@link' => Link::fromTextAndUrl($this->t('Click here'), Url::fromUri('https://developer.paypal.com/docs/classic/api/apiCredentials/'))->toString()]),
      '#open' => TRUE,
    );
    $form['apiplus']['apiplus_username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API username'),
      '#default_value' => $this->configuration['apiplus']['apiplus_username'],
    );
    $form['apiplus']['apiplus_password'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API password'),
      '#default_value' => $this->configuration['apiplus']['apiplus_password'],
    );
    $form['apiplus']['apiplus_signature'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Signature'),
      '#default_value' => $this->configuration['apiplus']['apiplus_signature'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['wpsplus_email'] = trim($form_state->getValue('wpsplus_email'));
    $this->configuration['wppplus_server'] = $form_state->getValue('wppplus_server');
    $this->configuration['apiplus']['apiplus_username'] = $form_state->getValue(['settings', 'apiplus', 'apiplus_username']);
    $this->configuration['apiplus']['apiplus_password'] = $form_state->getValue(['settings', 'apiplus', 'apiplus_password']);
    $this->configuration['apiplus']['apiplus_signature'] = $form_state->getValue(['settings', 'apiplus', 'apiplus_signature']);
  }

}
