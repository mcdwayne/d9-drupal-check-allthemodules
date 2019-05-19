<?php

namespace Drupal\tfl\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tfl\Controller\Api2Factor;
/**
 * {@inheritdoc}
 */
class Login2FactorAdminConfigForm extends ConfigFormBase {
  
  /**
   * Api2Factor.
   *
   * @var \Drupal\tfl\Controller\Api2Factor
   */
  protected $api2Factor;

  /**
   * Constructor method.
   *
   *
   */
  public function __construct() {
    $this->api2Factor = new Api2Factor();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tfl_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tfl.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tfl.settings');

    $form['clear'] = [
      '#type' => 'details',
      '#title' => $this->t('Clear all cache'),
      '#open' => TRUE,
    ];
    $form['clear']['cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable to clear all cache. (Sometimes configuration changes would not come due to cache.)'),
      '#default_value' => $config->get('cache'),
    ];
    $form['2factor_api'] = [
      '#type' => 'details',
      '#title' => $this->t('API configuration'),
      '#open' => TRUE,
    ];
    $form['2factor_api']['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#required' => TRUE,
      '#default_value' => $config->get('apikey'),
      '#description' => $this->t('Please enter 2Factor api key.'),
      '#attributes' => [
        'placeholder' => $this->t('Add api key.'),
      ],
    ];

    $form['2factor_status'] = [
      '#type' => 'details',
      '#title' => $this->t('Status'),
      '#open' => TRUE,
    ];
    $form['2factor_status']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable 2Factor authentication on login.'),
      '#default_value' => $config->get('status'),
    ];
    $form['2factor_type'] = [
      '#type' => 'details',
      '#title' => $this->t('OTP TYPE'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          'select[name="status"]' => [
            ['checked' => TRUE],
          ],
        ],
      ],
    ];
    $form['2factor_type']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        '' => 'Select',
        'SMS' => 'SMS',
        'VOICE' => 'VOICE',
      ],
      '#default_value' => $config->get('type'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $apikey = trim($form_state->getValue('apikey'));
    $type = $form_state->getValue('type');
    $regex = '/^([a-z0-9]+-)*[a-z0-9]+$/i';
    $api_data = $this->api2Factor->isValidApiKey($apikey, $type); 
    if (empty($api_data)) {
      $form_state->setError($form, $this->t('API connection not found. Please check your internet connection.'));
    }
    else if (isset($api_data) && $api_data->Status == 'Error') {
      $form_state->setError($form, $api_data->Details);
    }
    else if (!preg_match($regex, $apikey)) {
      $form_state->setErrorByName('apikey', $this->t('Invalid API Key - No Account Exists.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $apikey = trim($form_state->getValue('apikey'));
    $status = $form_state->getValue('status');
    $type = $form_state->getValue('type');
    $cache = $form_state->getValue('cache');

    $this->config('tfl.settings')->set('apikey', $apikey)->save();
    $this->config('tfl.settings')->set('status', $status)->save();
    $this->config('tfl.settings')->set('type', $type)->save();
    $this->config('tfl.settings')->set('cache', $cache)->save();
    if ($cache == 1) {
      drupal_flush_all_caches();
    }
    $api_data = $this->api2Factor->isValidApiKey($apikey, $type); 
    if (isset($api_data) && $api_data->Status == 'Success' && $api_data->Details < 10 && $api_data->Details != 0) {
      drupal_set_message($this->t('Your 2Factor.in '. $type .' OTP balance is low!'), 'warning');
    }
    else if (isset($api_data) && $api_data->Status == 'Success' && $api_data->Details == 0) {
      drupal_set_message($this->t('Your 2Factor.in '. $type .' OTP balance is zero!'), 'warning');
    }
    parent::submitForm($form, $form_state);
  }

}
