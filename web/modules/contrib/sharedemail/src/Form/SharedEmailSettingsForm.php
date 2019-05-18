<?php

namespace Drupal\sharedemail\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SharedEmailSettingsForm.
 *
 * @package Drupal\sharedemail\Form
 */
class SharedEmailSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sharedemail_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sharedemail.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('sharedemail.settings');

    // Shared email message text field.
    $form['sharedemail_msg'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Shared E-mail message'),
      '#default_value' => $config->get('sharedemail_msg'),
      '#description' => $this->t('Warning message that is only displayed to users with appropriate permission, when they choose to save an e-mail address already used by another user.'),
    ];

    $form['sharedemail_allowed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shared E-mail address whitelist'),
      '#default_value' => $config->get('sharedemail_allowed'),
      '#description' => $this->t('Comma separated list of email addresses that may be used to share. Leave blank to allow any.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the preferences.
    $this
      ->config('sharedemail.settings')
      ->set('sharedemail_msg', $form_state->getValue('sharedemail_msg'))
      ->set('sharedemail_allowed', $form_state->getValue('sharedemail_allowed'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
