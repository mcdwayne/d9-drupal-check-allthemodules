<?php

namespace Drupal\node_paypal_payment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configration form for module this module.
 */
class NPPConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'npp_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('node_paypal_payment.settings');

    $form['onoff'] = [
      '#type'  => 'details',
      '#title' => $this->t('General options'),
      '#open' => TRUE,
    ];

    $form['onoff']['npp_enabled'] = [
      '#type' => 'radios',
      '#title' => $this->t('Enable PayPal payment for node'),
      '#default_value' => $config->get('npp_enabled') ? $config->get('npp_enabled') : 'off',
      '#options' => ['on' => $this->t('On'), 'off' => $this->t('Off')],
      '#description' => $this->t('To enable PayPal payment for your content you must turn it on here first.'),
    ];

    $content_type_options = [];
    $content_types = node_type_get_types();

    foreach ($content_types as $machine_name => $content_type) {
      $label = $content_type->label();
      $content_type_options[$machine_name] = $label;
    }

    $form['onoff']['npp_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Choose content types'),
      '#default_value' => $config->get('npp_content_types') ? $config->get('npp_content_types') : [],
      '#options' => $content_type_options,
      '#description' => $this->t('Allows PayPal payment for selected content types'),
    ];

    $form['payment_options'] = [
      '#type'  => 'details',
      '#title' => $this->t('Payment options'),
      '#open' => TRUE,
    ];

    $form['payment_options']['npp_email'] = [
      '#type' => 'email',
      '#title' => $this->t('PayPal email ID'),
      '#default_value' => $config->get('npp_email'),
      '#description' => $this->t('PayPal email address to recieve payments.'),
    ];

    $form['payment_options']['npp_amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount'),
      '#default_value' => $config->get('npp_amount'),
    ];

    $form['payment_options']['npp_currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency for payment'),
      '#default_value' => $config->get('npp_currency'),
      '#options' => ['USD' => 'USD', 'GBP' => 'GBP'],
    ];

    $form['payment_options']['npp_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('PayPal payment mode'),
      '#default_value' => $config->get('npp_mode'),
      '#options' => ['live' => $this->t('Live'), 'sandbox' => $this->t('Sandbox')],
    ];

    $form['additional'] = [
      '#type'  => 'details',
      '#title' => $this->t('Additional settings'),
      '#open' => TRUE,
    ];

    $form['additional']['npp_success_path'] = [
      '#type' => 'radios',
      '#title' => $this->t('Redirect user after successfull payment'),
      '#default_value' => $config->get('npp_success_path') ? $config->get('npp_success_path') : 'node',
      '#options' => ['node' => $this->t('Content detail page'), 'dafault' => $this->t('Default thank you page')],
    ];

    $form['additional']['npp_success_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Thank you message'),
      '#default_value' => $config->get('npp_success_message') ? $config->get('npp_success_message') : 'Thank you for your payment. Your content has been created successfully.',
    ];

    $form['additional']['npp_cancel_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Cancel message'),
      '#default_value' => $config->get('npp_cancel_message') ? $config->get('npp_cancel_message') : 'Your Payment has been failed. Please try to contact to your site administrator.',
    ];

    $form['additional']['npp_submit_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label for content submit button'),
      '#default_value' => $config->get('npp_submit_label') ? $config->get('npp_submit_label') : $this->t('Save and Pay'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($values['npp_enabled'] == 'on') {
      if ($values['npp_email'] == '') {
        $form_state->setErrorByName('npp_email', $this->t('You must enter a PayPal email ID.'));
      }
      if ($values['npp_amount'] == '') {
        $form_state->setErrorByName('npp_amount', $this->t('You must enter Amount.'));
      }
      else {
        if (!is_numeric($values['npp_amount'])) {
          $form_state->setErrorByName('npp_amount', $this->t('Please enter a valid Amount.'));
        }
      }
      if ($values['npp_currency'] == '') {
        $form_state->setErrorByName('npp_currency', $this->t('You must select Currency for payment.'));
      }

      if ($values['npp_mode'] == '') {
        $form_state->setErrorByName('npp_mode', $this->t('You must select payment mode.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable('node_paypal_payment.settings');

    $config->set('npp_enabled', $values['npp_enabled'])
      ->set('npp_content_types', $values['npp_content_types'])
      ->set('npp_email', $values['npp_email'])
      ->set('npp_amount', $values['npp_amount'])
      ->set('npp_currency', $values['npp_currency'])
      ->set('npp_mode', $values['npp_mode'])
      ->set('npp_success_path', $values['npp_success_path'])
      ->set('npp_success_message', $values['npp_success_message'])
      ->set('npp_cancel_message', $values['npp_cancel_message'])
      ->set('npp_submit_label', $values['npp_submit_label'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @todo - Flesh this out.
   */
  public function getEditableConfigNames() {}

}
