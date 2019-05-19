<?php

namespace Drupal\twilio\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\twilio\Controller\TwilioController;

/**
 * Admin form for Twilio config.
 */
class TwilioAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twilio_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('twilio.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['twilio.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['account'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Twilio Account SID'),
      '#default_value' => $this->config('twilio.settings')->get('account'),
      '#description' => $this->t('Enter your Twilio account id'),
    ];
    $form['token'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Twilio Auth Token'),
      '#default_value' => $this->config('twilio.settings')->get('token'),
      '#description' => $this->t('Enter your Twilio token id'),
    ];
    $form['number'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Twilio Phone Number'),
      '#default_value' => $this->config('twilio.settings')->get('number'),
      '#description' => $this->t('Enter your Twilio phone number'),
    ];
    $form['long_sms'] = [
      '#type' => 'radios',
      '#title' => $this->t('Long SMS handling'),
      '#description' => $this->t('How would you like to handle SMS messages longer than 160 characters.'),
      '#options' => [
        $this->t('Send multiple messages'),
        $this->t('Truncate message to 160 characters'),
      ],
      '#default_value' => $this->config('twilio.settings')->get('long_sms'),
    ];
    $form['registration_form'] = [
      '#type' => 'radios',
      '#title' => $this->t('Show mobile fields during user registration'),
      '#description' => $this->t('Specify if the site should collect mobile information during registration.'),
      '#options' => [
        $this->t('Disabled'),
        $this->t('Optional'),
        $this->t('Required'),
      ],
      '#default_value' => $this->config('twilio.settings')->get('registration_form'),
    ];

    $form['twilio_country_codes_container'] = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => $this->t('Country codes'),
      '#description' => $this->t('Select the country codes you would like available, If none are selected all will be available.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['twilio_country_codes_container']['country_codes'] = [
      '#type' => 'checkboxes',
      '#options' => TwilioController::countryDialCodes(TRUE),
      '#default_value' => $this->config('twilio.settings')->get('twilio_country_codes_container')['country_codes'],
    ];
    // Expose the callback URLs to the user for convenience.
    $form['twilio_callback_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Module callbacks'),
      '#description' => $this->t('Enter these callback addresses into your Twilio phone number configuration on the Twilio dashboard to allow your site to respond to incoming voice calls and SMS messages.'),
    ];

    // Initialize URL variables.
    $voice_callback = $GLOBALS['base_url'] . '/twilio/voice';
    $sms_callback = $GLOBALS['base_url'] . '/twilio/sms';

    $form['twilio_callback_container']['voice_callback'] = [
      '#type' => 'item',
      '#title' => $this->t('Voice request URL'),
      '#markup' => '<p>' . $voice_callback . '</p>',
    ];

    $form['twilio_callback_container']['sms_callback'] = [
      '#type' => 'item',
      '#title' => $this->t('SMS request URL'),
      '#markup' => '<p>' . $sms_callback . '</p>',
    ];

    return parent::buildForm($form, $form_state);
  }

}
