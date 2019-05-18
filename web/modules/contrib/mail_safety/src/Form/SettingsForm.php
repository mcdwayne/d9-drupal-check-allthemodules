<?php
namespace Drupal\mail_safety\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form for the mail safety settings.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_safety_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mail_safety.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mail_safety.settings');
    
    $form['enabled'] = array(
      '#title' => t('Stop outgoing mails'),
      '#type' => 'checkbox',
      '#description' => t('When Mail Safety is enabled it will stop all outgoing emails from being sent and will send them to either the dashboard and/or the defaut mail address instead.'),
      '#default_value' => $config->get('enabled'),
    );

    $form['send_mail_to_dashboard'] = array(
      '#title' => t('Send mail to dashboard'),
      '#type' => 'checkbox',
      '#description' => t('If enabled, all mails will be sent to the dashboard'),
      '#default_value' => $config->get('send_mail_to_dashboard'),
    );

    $form['send_mail_to_default_mail'] = array(
      '#title' => t('Send mail to default mail'),
      '#type' => 'checkbox',
      '#description' => t('If enabled, all mails will be sent to the the default mail address'),
      '#default_value' => $config->get('send_mail_to_default_mail'),
    );

    $form['default_mail_address'] = array(
      '#title' => t('Default mail address'),
      '#type' => 'textfield',
      '#description' => t('The default email address that outgoing e-mails will be rerouted to if enabled.'),
      '#default_value' => $config->get('default_mail_address'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('mail_safety.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('send_mail_to_dashboard', $form_state->getValue('send_mail_to_dashboard'))
      ->set('send_mail_to_default_mail', $form_state->getValue('send_mail_to_default_mail'))
      ->set('default_mail_address', $form_state->getValue('default_mail_address'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
