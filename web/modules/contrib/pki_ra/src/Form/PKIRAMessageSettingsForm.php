<?php

namespace Drupal\pki_ra\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * PKI Registration Authority message configuration settings form.
 */
class PKIRAMessageSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pki_ra_settings_choose_messaging_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pki_ra.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pki_ra.settings');

    $form['intro'] = [
      '#type' => 'text_format',
      '#format' => NULL,
      '#title' => $this->t('Introductory message at registration'),
      '#description' => $this->t('What you would like displayed to users at the beginning of the registration process, before they start.'),
      '#default_value' => $config->get('messages.introduction')['value'] ?: '',
    ];

    $form['verification_mail_success'] = [
      '#type' => 'text_format',
      '#format' => NULL,
      '#title' => $this->t('Verification e-mail sent'),
      '#description' => $this->t('Displayed after a verification e-mail was successfully sent.'),
      '#default_value' => $config->get('messages.verification_mail_success')['value'] ?: '',
    ];

    $form['verification_mail_failure'] = [
      '#type' => 'text_format',
      '#format' => NULL,
      '#title' => $this->t('Verification e-mail not sent'),
      '#description' => $this->t('Displayed after a verification e-mail could not be sent.'),
      '#default_value' => $config->get('messages.verification_mail_failure')['value'] ?: '',
    ];

    $form['verification_email_body'] = [
      '#type' => 'text_format',
      '#format' => NULL,
      '#title' => $this->t('Verification e-mail contents'),
      '#description' => $this->t('Contents of the e-mail sent to registering users.'),
      '#default_value' => $config->get('messages.verification_email_body')['value'] ?: '',
    ];

    $form['email_address_confirmation'] = [
      '#type' => 'text_format',
      '#format' => NULL,
      '#title' => $this->t('E-mail address confirmation'),
      '#description' => $this->t('Displayed on the e-mail address validation form.'),
      '#default_value' => $config->get('messages.email_address_confirmation')['value'] ?: '',
    ];

    $form['email_address_validated'] = [
      '#type' => 'text_format',
      '#format' => NULL,
      '#title' => $this->t('Successful e-mail address validation'),
      '#description' => $this->t('Displayed after an e-mail has been successfully validated.'),
      '#default_value' => $config->get('messages.email_address_validated')['value'] ?: '',
    ];

    $form['certificate_generation_header'] = [
      '#type' => 'text_format',
      '#format' => NULL,
      '#title' => $this->t('Header for the certificate generation form'),
      '#description' => $this->t('Displayed at the top of the certificate generation form.'),
      '#default_value' => $config->get('messages.certificate_generation_header')['value'] ?: '',
    ];

    $form['certificate_generation_help'] = [
      '#type' => 'text_format',
      '#format' => NULL,
      '#title' => $this->t('Help for the certificate generation form'),
      '#description' => $this->t('Displayed as the help text on the certificate generation form.'),
      '#default_value' => $config->get('messages.certificate_generation_help')['value'] ?: '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('pki_ra.settings')
      ->set('messages.introduction', $form_state->getValue('intro'))
      ->set('messages.verification_mail_success', $form_state->getValue('verification_mail_success'))
      ->set('messages.verification_mail_failure', $form_state->getValue('verification_mail_failure'))
      ->set('messages.verification_email_body', $form_state->getValue('verification_email_body'))
      ->set('messages.email_address_confirmation', $form_state->getValue('email_address_confirmation'))
      ->set('messages.email_address_validated', $form_state->getValue('email_address_validated'))
      ->set('messages.certificate_generation_header', $form_state->getValue('certificate_generation_header'))
      ->set('messages.certificate_generation_help', $form_state->getValue('certificate_generation_help'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
