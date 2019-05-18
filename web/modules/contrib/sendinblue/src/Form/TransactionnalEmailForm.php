<?php

namespace Drupal\sendinblue\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\sendinblue\SendinblueManager;

/**
 * Class Form Transactionnal emails SMTP.
 */
class TransactionnalEmailForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return SendinblueManager::CONFIG_SETTINGS_SEND_EMAIL;
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $smtp_details = \Drupal::config(SendinblueManager::CONFIG_SETTINGS_SEND_EMAIL)
      ->get(SendinblueManager::SMTP_DETAILS, '');
    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable(SendinblueManager::CONFIG_SETTINGS_SEND_EMAIL);

    if ($smtp_details == FALSE) {
      $smtp_details = SendinblueManager::updateSmtpDetails();
    }
    if (($smtp_details == FALSE) || ($smtp_details['relay'] == FALSE)) {
      $config->set('sendinblue_on', 0)->save();
      $smtp_available = FALSE;
    }
    else {
      $smtp_available = TRUE;
    }

    $form = [];
    if ($smtp_available == FALSE) {
      $form['sendinblue_alert'] = [
        '#type' => 'markup',
        '#prefix' => '<div id="sendinblue_alert_area" style="padding: 10px;background-color: #fef5f1;color: #8c2e0b;border-color: #ed541d;border-width: 1px;border-style: solid;">',
        '#markup' => t('Current you can not use SendinBlue SMTP. Please confirm at <a href="@smtp-sendinblue" target="_blank">Here</a>', ['@smtp-sendinblue' => 'https://mysmtp.sendinblue.com/?utm_source=drupal_plugin&utm_medium=plugin&utm_campaign=module_link']),
        '#suffix' => '</div>',
        '#tree' => TRUE,
      ];
    }

    $form['sendinblue_on'] = [
      '#type' => 'radios',
      '#title' => t('Send emails through SendinBlue SMTP'),
      '#default_value' => \Drupal::config(SendinblueManager::CONFIG_SETTINGS_SEND_EMAIL)
        ->get('sendinblue_on', ''),
      '#description' => t('Choose "Yes" if you want to use SendinBlue SMTP to send transactional emails.'),
      '#options' => [1 => t('Yes'), 0 => t('No')],
      '#disabled' => ($smtp_available == TRUE) ? FALSE : TRUE,
    ];

    $form['sendinblue_to_email'] = [
      '#type' => 'textfield',
      '#title' => t('Enter email to send a test'),
      '#description' => t('Select here the email address you want to send a test email to.'),
      '#disabled' => ($smtp_available == TRUE) ? FALSE : TRUE,
      '#states' => [
        // Hide unless needed.
        'visible' => [
          ':input[name="sendinblue_on"]' => ['value' => 1],
        ],
        'required' => [
          ':input[name="sendinblue_on"]' => ['value' => 1],
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save Settings'),
      '#disabled' => ($smtp_available == TRUE) ? FALSE : TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $sendinblue_on = $form_state->getValue('sendinblue_on');
    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable(SendinblueManager::CONFIG_SETTINGS_SEND_EMAIL);

    $send_email = $form_state->getValue('sendinblue_to_email');

    if ($sendinblue_on == '1') {
      $smtp_details = \Drupal::config(SendinblueManager::CONFIG_SETTINGS_SEND_EMAIL)
        ->get(SendinblueManager::SMTP_DETAILS, '');

      if ($smtp_details == NULL) {
        $smtp_details = SendinblueManager::updateSmtpDetails();
      }
      if ($smtp_details['relay'] == FALSE) {
        $sendinblue_on = 0;
      }
    }
    $config->set('sendinblue_on', $sendinblue_on)->save();

    if ($send_email != '') {
      if (!\Drupal::service('email.validator')->isValid($send_email)) {
        $form_state->setErrorByName('sendinblue_to_email', t('The email address is invalid.'));
        return;
      }
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $send_email = $form_state->getValue('sendinblue_to_email');
    SendinblueManager::sendEmail('test', $send_email, '', '');

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [SendinblueManager::CONFIG_SETTINGS_SEND_EMAIL];
  }

}
