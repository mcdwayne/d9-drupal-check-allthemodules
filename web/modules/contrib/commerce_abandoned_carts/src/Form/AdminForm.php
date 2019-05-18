<?php

namespace Drupal\commerce_abandoned_carts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Commerce Abandoned Carts settings form.
 */
class AdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_abandoned_carts_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_abandoned_carts.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_abandoned_carts.settings');

    $form = [];
    $form['commerce_abandoned_carts']['timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Send timeout'),
      '#default_value' => $config->get('timeout'),
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('How many minutes to wait before sending the abandoned cart message in <strong>minutes</strong>. Note that there are 1440 minutes in one day.'),
    ];

    $form['commerce_abandoned_carts']['history_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('History limit'),
      '#default_value' => $config->get('history_limit'),
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('What is the limit (in minutes) to how far back to search for abandoned carts. Default is 15 days.'),
    ];

    // @todo replace with queue.
    $options = [
      5 => '5',
      10 => '10',
      25 => '25',
      50 => '50',
      75 => '75',
      100 => '100',
    ];

    $form['commerce_abandoned_carts']['batch_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Batch limit'),
      '#options' => $options,
      '#default_value' => $config->get('batch_limit'),
      '#description' => $this->t('What is the maximum emails to send per cron run? Note, larger batches may cause performance issues.'),
    ];

    $form['commerce_abandoned_carts']['from_email'] = [
      '#type' => 'email',
      '#title' => $this->t('From email address'),
      '#default_value' => $config->get('from_email'),
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('Enter the email address to send the emails from. Leave blank to use site-wide email address.'),
    ];

    $form['commerce_abandoned_carts']['from_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From name'),
      '#default_value' => $config->get('from_name'),
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('Enter the name to send the emails from.  Leave blank to use site name.'),
    ];

    $form['commerce_abandoned_carts']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('subject'),
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('Enter the subject of the email.'),
    ];

    $form['commerce_abandoned_carts']['customer_service_phone_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer service phone number'),
      '#default_value' => $config->get('customer_service_phone_number'),
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('Enter a phone number to be displayed in the email template for customers who may have had trouble checking out. Leave empty to obmit from email.'),
    ];

    $form['commerce_abandoned_carts']['bcc_active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send BCC'),
      '#default_value' => $config->get('bcc_active'),
      '#description' => $this->t('If enabled, a Blind Carbon Copy of all Abandoned Cart messages is send to an admin account for monitoring.'),
    ];

    $form['commerce_abandoned_carts']['bcc_email'] = [
      '#type' => 'email',
      '#title' => $this->t('BCC email address'),
      '#default_value' => $config->get('bcc_email'),
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('Enter the email address to a BBC to.'),
      '#states' => [
        'required' => [
          ':input[name="bcc_active"]' => ['checked' => TRUE],
        ],
        'visible' => [
          ':input[name="bcc_active"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['commerce_abandoned_carts']['testmode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test mode'),
      '#default_value' => $config->get('testmode'),
      '#description' => $this->t('When test mode is active all abandoned carts messages will be sent to the test email address instead of cart owner for testing purposes. When in test module the status of the message is not updated so the same messages will be sent on each cron run.'),
    ];

    $form['commerce_abandoned_carts']['testmode_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Test mode email address'),
      '#default_value' => $config->get('testmode_email'),
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('Enter the email address to send the test emails to.'),
      '#states' => [
        'required' => [
          ':input[name="testmode"]' => ['checked' => TRUE],
        ],
        'visible' => [
          ':input[name="testmode"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('bcc_active') && !$form_state->getValue('bcc_email')) {
      $form_state->setErrorByName('commerce_abandoned_carts_testmode_email', $this->t('@name field is required when @other is enabled.', [
        '@name' => $this->t('BCC email address'),
        '@other' => $this->t('Send BCC'),
      ]));
    }
    if ($form_state->getValue('testmode') && !$form_state->getValue('testmode_email')) {
      $form_state->setErrorByName('commerce_abandoned_carts_testmode_email', $this->t('@name field is required when @other is enabled.', [
        '@name' => $this->t('Test mode email address'),
        '@other' => $this->t('Test mode'),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('commerce_abandoned_carts.settings')
      ->set('timeout', $form_state->getValue('timeout'))
      ->set('history_limit', $form_state->getValue('history_limit'))
      ->set('batch_limit', $form_state->getValue('batch_limit'))
      ->set('from_email', $form_state->getValue('from_email'))
      ->set('from_name', $form_state->getValue('from_name'))
      ->set('subject', $form_state->getValue('subject'))
      ->set('customer_service_phone_number', $form_state->getValue('customer_service_phone_number'))
      ->set('bcc_active', $form_state->getValue('bcc_active'))
      ->set('bcc_email', $form_state->getValue('bcc_email'))
      ->set('testmode', $form_state->getValue('testmode'))
      ->set('testmode_email', $form_state->getValue('testmode_email'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
