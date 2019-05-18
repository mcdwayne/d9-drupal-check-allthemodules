<?php
/**
 * @file
 * This is the GlobalRedirect admin include which provides an interface to global redirect to change some of the default settings
 * Contains \Drupal\globalredirect\Form\GlobalredirectSettingsForm.
 */

namespace Drupal\authy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form to configure module settings.
 */
class AuthySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'authy_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['authy.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get all settings
    $config = $this->configFactory->get('authy.settings');
    $settings = $config->get();

    $form['settings'] = array(
      '#tree' => TRUE,
    );

    $form['settings']['authy_host_uri'] = array(
    '#title' => t('Authy API'),
    '#type' => 'select',
    '#options' => array(
      'https://api.authy.com' => t('Production API'),
      'http://sandbox-api.authy.com' => t('Sandbox API'),
    ),
    '#default_value' => $settings['authy_host_uri'],
    '#required' => TRUE,
  );

  $form['settings']['authy_application'] = array(
    '#type' => 'textfield',
    '#title' => t('Authy Application name'),
    '#default_value' => $settings['authy_application'],
    '#required' => TRUE,
  );

  $form['settings']['authy_api_key'] = array(
    '#type' => 'textfield',
    '#title' => t('API Key'),
    '#default_value' => $settings['authy_api_key'],
    '#description' => t('Your application API Key.'),
    '#required' => TRUE,
  );

  $form['settings']['authy_trigger_threshold'] = array(
    '#type' => 'select',
    '#title' => t('Trigger threshold'),
    '#default_value' => $settings['authy_trigger_threshold'],
    '#options' => array(
      '2' => '2',
      '3' => '3',
      '4' => '4',
      '5' => '5',
      '7' => '7',
      '10' => '10'
    ),
    '#description' => t('Trigger the authy failed multiple times rule after this many failed attempts.'),
    '#required' => TRUE,
  );

  $authy_about = t(
    'Traditional security relies on something you know; your username and password. Authy increases your security by also requiring something you have.<br>'."\n".
    'Authy is available for iPhone, Android or via normal SMS. When required during logging in or when entering secure areas, you will be asked to enter a token.'."\n".
    'You simply open your Authy app and copy the short code to the website. If the app is not working for you, you can request an SMS be sent as a backup method.<br>'."\n".
    'Our server will then confirm your token is as expected. If your password is ever compromised, your account will still be safe.'
  );

  $form['settings']['authy_about'] = array(
    '#type' => 'textarea',
    '#title' => t('About text'),
    '#default_value' => $settings['authy_about'],
    '#description' => t('Enter a short about text that will be displayed on the users Authy configuration page.'),
    '#required' => FALSE,
  );

  $form['settings']['authy_forms'] = array(
    '#type' => 'textarea',
    '#title' => t('Require Authy in the following forms'),
    '#default_value' => $settings['authy_forms'],
    '#description' => t('Enter form IDs, one on each line.'),
    '#required' => FALSE,
  );

  $form['settings']['authy_show_form_id'] = array(
    '#title' => t('Show Drupal form id'),
    '#type' => 'select',
    '#options' => array(
      'y' => t('Yes'),
      'n' => t('No'),
    ),
    '#default_value' => $settings['authy_show_form_id'],
    '#required' => TRUE,
  );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   * Compares the submitted settings to the defaults and unsets any that are equal. This was we only store overrides.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get config factory
    $config = $this->configFactory->getEditable('authy.settings');

    $form_values = $form_state->getValue(['settings']);

    $config
      ->set('authy_host_uri', $form_values['authy_host_uri'])
      ->set('authy_application', $form_values['authy_application'])
      ->set('authy_api_key', $form_values['authy_api_key'])
      ->set('authy_trigger_threshold', $form_values['authy_trigger_threshold'])
      ->set('authy_about', $form_values['authy_about'])
      ->set('authy_forms', $form_values['authy_forms'])
      ->set('authy_show_form_id', $form_values['authy_show_form_id'])
      ->save();

    parent::submitForm($form, $form_state);

  }

  /**
   * Returns an associative array of default settings
   * @return array
   */
  public function getDefaultSettings() {

    $defaults = array(
      'authy_host_uri' => 1,
    );

    return $defaults;
  }

}