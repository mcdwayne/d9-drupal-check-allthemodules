<?php

namespace Drupal\uc_paymentexpress\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines the DPS payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "dps",
 *   name = @Translation("DPS Checkout"),
 * )
 */
class DpsPayCheckout extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'px_access_url' => 'https://sec.paymentexpress.com/pxaccess/pxpay.aspx',
      'px_access_uid' => '',
      'px_access_key' => '',
      'px_post_url' => '',
      'px_post_uid' => '',
      'px_post_key' => '',
      'dps_test_mod' => TRUE,
      'px_access_test_url' => 'https://sec.paymentexpress.com/pxaccess/pxpay.aspx',
      'px_access_test_uid' => '',
      'px_access_test_key' => '',
      'px_post_test_url' => '',
      'px_post_test_uid' => '',
      'px_post_test_key' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // Express Checkout specific settings.
    $form['px_access_url'] = array(
      '#type' => 'textfield',
      '#title' => t('PX Access URL'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['px_access_url'],
    );
    $form['px_access_uid'] = array(
      '#type' => 'textfield',
      '#title' => t('PX Access UID'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['px_access_uid'],
    );
    $form['px_access_key'] = array(
      '#type' => 'textfield',
      '#title' => t('PX Access Key'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['px_access_key'],
    );
    $form['px_post_url'] = array(
      '#type' => 'textfield',
      '#title' => t('PX Post URL'),
      '#default_value' => $this->configuration['px_post_url'],
    );
    $form['px_post_uid'] = array(
      '#type' => 'textfield',
      '#title' => t('PX Post UID'),
      '#default_value' => $this->configuration['px_post_uid'],
    );
    $form['px_post_key'] = array(
      '#type' => 'textfield',
      '#title' => t('PX Post Key'),
      '#default_value' => $this->configuration['px_post_key'],
    );

    $form['dps_test_mod'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Run payment based on test account setting.'),
      '#default_value' => $this->configuration['dps_test_mod'],
    );

    $form['px_access_test_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Test PX Access URL'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['px_access_test_url'],
    );
    $form['px_access_test_uid'] = array(
      '#type' => 'textfield',
      '#title' => t('Test PX Access UID'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['px_access_test_uid'],
    );
    $form['px_access_test_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Test PX Access Key'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['px_access_test_key'],
    );
    $form['px_post_test_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Test PX Post URL'),
      '#default_value' => $this->configuration['px_post_test_url'],
    );
    $form['px_post_test_uid'] = array(
      '#type' => 'textfield',
      '#title' => t('Test PX Post UID'),
      '#default_value' => $this->configuration['px_post_test_uid'],
    );
    $form['px_post_test_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Test PX Post Key'),
      '#default_value' => $this->configuration['px_post_test_key'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['px_access_url'] = $form_state->getValue('px_access_url');
    $this->configuration['px_access_uid'] = $form_state->getValue('px_access_uid');
    $this->configuration['px_access_key'] = $form_state->getValue('px_access_key');
    $this->configuration['px_post_url'] = $form_state->getValue('px_post_url');
    $this->configuration['px_post_uid'] = $form_state->getValue('px_post_uid');
    $this->configuration['px_post_key'] = $form_state->getValue('px_post_key');
    $this->configuration['dps_test_mod'] = $form_state->getValue('dps_test_mod');
    $this->configuration['px_access_test_url'] = $form_state->getValue('px_access_test_url');
    $this->configuration['px_access_test_uid'] = $form_state->getValue('px_access_test_uid');
    $this->configuration['px_access_test_key'] = $form_state->getValue('px_access_test_key');
    $this->configuration['px_post_test_url'] = $form_state->getValue('px_post_test_url');
    $this->configuration['px_post_test_uid'] = $form_state->getValue('px_post_test_uid');
    $this->configuration['px_post_test_key'] = $form_state->getValue('px_post_test_key');
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {

  }

}
