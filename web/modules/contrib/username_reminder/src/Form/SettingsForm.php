<?php

namespace Drupal\username_reminder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures Username Reminder settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'username_reminder_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'username_reminder.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $email_token_help = $this->t('Available variables are: [site:name], [site:url], [user:display-name], [user:account-name], [user:mail], [site:login-url], [site:url-brief], [user:edit-url], [user:one-time-login-url], [user:cancel-url].');
    $config = $this->config('username_reminder.settings');
    $form['email_username_reminder'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Email'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => $this->t('Edit the email messages sent to users who request a username reminder.') . ' ' . $email_token_help,
    ];
    $form['email_username_reminder']['username_reminder_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('reminder.subject'),
      '#maxlength' => 180,
    ];
    $form['email_username_reminder']['username_reminder_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('reminder.body'),
      '#rows' => 12,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('username_reminder.settings')
      ->set('reminder.subject', $values['username_reminder_subject'])
      ->set('reminder.body', $values['username_reminder_body'])
      ->save();
  }

}
