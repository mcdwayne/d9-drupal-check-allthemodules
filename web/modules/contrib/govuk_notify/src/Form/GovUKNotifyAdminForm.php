<?php

namespace Drupal\govuk_notify\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin form for GovUK Notify settings.
 */
class GovUKNotifyAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'govuk_notify.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'govuk_notify_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormTitle() {
    return 'GovUK Notify Configuration Settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('govuk_notify.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('The API key to use. You can generate an API key by logging in to GOV.UK Notify and going to the API integration page.'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['default_template_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default template ID'),
      '#description' => $this->t('The template ID to use if one is not specified. This template should have one single placeholderfor the subject, ((subject)), and one in the message body ((message))'),
      '#default_value' => $config->get('default_template_id'),
    ];

    $form['default_sms_template_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default SMS template ID'),
      '#description' => $this->t('The template ID to use for SMS messages if one is not specified. This template should have one single placeholderfor the message body ((message))'),
      '#default_value' => $config->get('default_sms_template_id'),
    ];

    $mail_interfaces = $this->config('system.mail')->get('interface');
    $form['send_system_emails'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use GOVUK Notify to send system emails'),
      '#description' => $this->t("Ensures that all system emails are sent using GovUK Notify"),
      '#default_value' => ($mail_interfaces['default'] == 'govuk_notify_mail'),
    ];

    $form['force_temporary_failure'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always force a temporary failure'),
      '#description' => $this->t("Ensures that all emails return a temporary failure. Requires that the API key is a test key (see https://www.notifications.service.gov.uk/integration_testing)."),
      '#default_value' => $config->get('force_temporary_failure'),
    ];

    $form['force_permanent_failure'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always force a permanent failure'),
      '#description' => $this->t("Ensures that all emails return a permanent failure. Requires that the API key is a test key (see https://www.notifications.service.gov.uk/integration_testing)."),
      '#default_value' => $config->get('force_permanent_failure'),
    ];

    $form['govuk_notify_email_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test email address'),
      '#description' => $this->t('If you enter an email address into this field, the system will attempt to send an email to that address using the govuk notify service. You can use this to test that you have entered the correct API key.'),
    ];

    $form['govuk_notify_sms_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test phone number for SMS'),
      '#description' => $this->t('If you enter a phone number into this field, the system will attempt to send an SMS to that phone the govuk notify service. You can use this to test that you have entered the correct API key.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * If the gov_notify_email_test field has been completed we try to send an
   * email to the default template.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('govuk_notify.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('default_template_id', $form_state->getValue('default_template_id'))
      ->set('default_sms_template_id', $form_state->getValue('default_sms_template_id'))
      ->set('force_temporary_failure', $form_state->getValue('force_temporary_failure'))
      ->set('force_permanent_failure', $form_state->getValue('force_permanent_failure'))
      ->save();
    Cache::invalidateTags(["govuk_notify_template:{$form_state->getValue('default_template_id')}"]);

    // Set the default mail interface to govuk_notify, if that's what we want.
    // Or if it was previously set to govuk_notify and we no longer want to use
    // that for default messages then set it back to php_mail.
    $mail_interfaces = $this->config('system.mail')->get('interface');
    if ($form_state->getValue('send_system_emails')) {
      $mail_interfaces['default'] = 'govuk_notify_mail';
      \Drupal::service('config.factory')->getEditable('system.mail')->set('interface', $mail_interfaces)->save();
    }
    else {
      if (isset($mail_interfaces['default']) && $mail_interfaces['default'] == 'govuk_notify_mail') {
        $mail_interfaces['default'] = 'php_mail';
        \Drupal::service('config.factory')->getEditable('system.mail')->set('interface', $mail_interfaces)->save();
      }
    }

    if (!empty($form_state->getValue('govuk_notify_email_test'))) {
      $mail_manager = \Drupal::service('plugin.manager.mail');
      $to = $form_state->getValue('govuk_notify_email_test');
      $langcode = \Drupal::currentUser()->getPreferredLangcode();
      $params['subject'] = t("This is a test message from the GovUK Drupal module.");
      $params['message'] = t("This is a test message. If you have received this message the GovUK Notify Drupal module is working succesfully");
      $send = TRUE;
      $result = $mail_manager->mail('govuk_notify', NULL, $to, $langcode, $params, NULL, $send);
      if ($result['result'] === FALSE) {
        drupal_set_message(t('There was a problem sending your test email and it was not sent.'), 'error');
      }
      else {
        drupal_set_message(t('Test email has been sent.'));
      }
    }

    if (!empty($form_state->getValue('govuk_notify_sms_test'))) {
      $mail_manager = \Drupal::service('plugin.manager.mail');
      $to = $form_state->getValue('govuk_notify_sms_test');
      $langcode = \Drupal::currentUser()->getPreferredLangcode();
      $params['message'] = t("This is a test SMS message. If you have received this message the GovUK Notify Drupal module is working succesfully");
      $send = TRUE;
      $result = $mail_manager->mail('govuk_notify', NULL, $to, $langcode, $params, NULL, $send);
      if ($result['result'] === FALSE) {
        drupal_set_message(t('There was a problem sending your test SMS and it was not sent.'), 'error');
      }
      else {
        drupal_set_message(t('Test SMS has been sent.'));
      }
    }
  }

}
