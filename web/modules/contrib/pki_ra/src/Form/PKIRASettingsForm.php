<?php

namespace Drupal\pki_ra\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\pki_ra\Services\PkiCertificationAuthorityService;

/**
 * PKI Registration Authority configuration settings form.
 */
class PKIRASettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pki_ra_settings_basic_form';
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

    $form['registration_confirmation_window'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registration confirmation window'),
      '#description' => $this->t('The number of days before unconfirmed registrations expire. Users will have this long to confirm their e-mail addresses before their registration records are deleted.'),
      '#default_value' => $config->get('registration_confirmation_window') ?: 2,
      '#required' => TRUE,
    ];

    $form['certificate_authority_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Certification Authority URL'),
      '#description' => $this->t('The URL of the certification authority (CA) such as <em>https://ca.example.com</em>. Non-HTTPS URLs should only be used in dev/testing environments. The hostname must be included in <em>$settings[\'trusted_host_patterns\']</em> in your settings(.local).php.'),
      '#default_value' => $config->get('certificate_authority_url') ?: '',
      '#required' => TRUE,
    ];

    $form['certificate_authority_authentication_header'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Certification Authority Authentication Header'),
      '#description' => $this->t('The authentication header to send along with any requests to the CA, usually in the form of an API key. Required for non-public service calls. Example: <code>Authorization: tN2lv652w6bcvCbNuYVJZdVpeNfIFYNdmNpQKnjzZmo</code>'),
      '#default_value' => $config->get('certificate_authority_authentication_header') ?: '',
    ];

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug logging'),
      '#description' => $this->t('Log additional debugging information, recording all data sent and received via Web service calls. <em>Do not enable on Production as secrets are logged as well.</em>'),
      '#default_value' => $config->get('debug') ?: 0,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $window = $form_state->getValue(['registration_confirmation_window']);
    if (!is_numeric($window) || ($window <= 0)) {
      $form_state->setErrorByName('registration_confirmation_window', $this->t('The window must be a number of days greater than zero.'));
    }

    $ca_url = $form_state->getValue(['certificate_authority_url']);
    if (!UrlHelper::isValid($ca_url, TRUE)) {
      $form_state->setErrorByName('certificate_authority_url', $this->t('Must be a valid URL.'));
    }

    $authentication_header = $form_state->getValue(['certificate_authority_authentication_header']);
    if (!PkiCertificationAuthorityService::authenticationIsProperlyFormatted($authentication_header)) {
      $form_state->setErrorByName('certificate_authority_authentication_header', $this->t('Must be in the form of a valid HTTP header.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('pki_ra.settings')
      ->set('registration_confirmation_window', $form_state->getValue('registration_confirmation_window'))
      ->set('certificate_authority_url', $form_state->getValue('certificate_authority_url'))
      ->set('certificate_authority_authentication_header', $form_state->getValue('certificate_authority_authentication_header'))
      ->set('debug', $form_state->getValue('debug'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
