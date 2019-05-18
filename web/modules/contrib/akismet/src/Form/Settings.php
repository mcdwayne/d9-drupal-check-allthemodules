<?php

namespace Drupal\akismet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\akismet\Client\DrupalClient;
use Drupal\akismet\Utility\AkismetUtilities;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures devel settings.
 */
class Settings extends ConfigFormBase {
  /**
   * Server communication failure fallback mode: Block all submissions of protected forms.
   */
  const AKISMET_FALLBACK_BLOCK = 0;


  /**
   * Server communication failure fallback mode: Accept all submissions of protected forms.
   */
  const AKISMET_FALLBACK_ACCEPT = 1;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'akismet_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'akismet.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    AkismetUtilities::displayAkismetTestModeWarning();

    $config = $this->config('akismet.settings');

    // Only check and display the status message if the form is being shown
    // for the first time and not when displayed again after submission.
    $check = empty($_POST);
    if ($check) {
      $status = AkismetUtilities::getAdminAPIKeyStatus($check);
      if ($status['isVerified'] && !$config->get('test_mode.enabled')) {
        \Drupal::messenger()->addMessage(t('Akismet verified your key. The service is operating correctly.'));
      }
    }

    // Keys are not #required to allow to install this module and configure it
    // later.
    $form['api_key'] = array(
        '#type' => 'textfield',
        '#title' => t('API key'),
        '#default_value' => $config->get('api_key'),
        '#description' => t('To obtain an API key, <a href="@signup-url">sign up</a> or log in to your <a href="@account-url">account</a>, add a subscription for this site, and copy the key into the field below.', array(
          '@signup-url' => 'https://akismet.com/signup',
          '@account-url' => 'https://akismet.com/account',
        )),
    );

    $form['fallback'] = array(
        '#type' => 'radios',
        '#title' => t('When the Akismet service is unavailable'),
        '#default_value' => $config->get('fallback'),
        '#options' => array(
            Settings::AKISMET_FALLBACK_ACCEPT => t('Accept all form submissions'),
            Settings::AKISMET_FALLBACK_BLOCK => t('Block all form submissions'),
        ),
    );

    $form['privacy_link'] = array(
        '#type' => 'checkbox',
        '#title' => t("Show a link to Akismet's privacy policy"),
        '#return_value' => true,
        '#default_value' => $config->get('privacy_link'),
        '#description' => t('Only applies to forms protected with text analysis. When disabling this option, you should inform visitors about the privacy of their data through other means.'),
    );

    $form['testing_mode'] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable testing mode'),
        '#return_value' => true,
        '#default_value' => $config->get('test_mode.enabled'),
        '#description' => t('Submitting "ham", "unsure", or "spam" triggers the corresponding behavior; image CAPTCHAs only respond to "correct" and audio CAPTCHAs only respond to "demo". Do not enable this option if this site is publicly accessible.'),
    );

    $form['advanced'] = array(
        '#type' => 'details',
        '#title' => t('Advanced configuration'),
        '#open' => FALSE,
    );
    // Lower severity numbers indicate a high severity level.
    $form['advanced']['log_level'] = array(
        '#type' => 'radios',
        '#title' => t('Akismet logging level warning'),
        '#options' => array(
            RfcLogLevel::WARNING => t('Only log warnings and errors'),
            RfcLogLevel::DEBUG => t('Log all Akismet messages'),
        ),
        '#default_value' => $config->get('log_level'),
    );
    $timeout = $config->get('connection_timeout_seconds');
    $form['advanced']['connection_timeout_seconds'] = array(
        '#type' => 'number',
        '#title' => t('Time-out when attempting to contact Akismet servers.'),
        '#description' => t('This is the length of time that a call to Akismet will wait before timing out.'),
        '#default_value' => !empty($timeout) ? $config->get('connection_timeout_seconds') : 3,
        '#size' => 5,
        '#field_suffix' => t('seconds'),
        '#required' => TRUE,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('akismet.settings');

    $config->set('api_key', $values['api_key'])
        ->set('fallback', $values['fallback'])
        ->set('privacy_link', $values['privacy_link'])
        ->set('test_mode.enabled', $values['testing_mode'])
        ->set('log_level', $values['log_level'])
        ->set('connection_timeout_seconds', $values['connection_timeout_seconds'])
        ->save();

    parent::submitForm($form, $form_state);
  }

}
