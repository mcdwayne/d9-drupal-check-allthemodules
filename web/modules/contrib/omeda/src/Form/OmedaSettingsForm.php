<?php

namespace Drupal\omeda\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\encryption\EncryptionService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Omeda settings for this site.
 */
class OmedaSettingsForm extends ConfigFormBase {

  /**
   * The encryption service.
   *
   * @var \Drupal\encryption\EncryptionService
   */
  protected $encryption;

  /**
   * Constructs a \Drupal\omeda\Form\OmedaSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\encryption\EncryptionService $encryption
   *   The encryption service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EncryptionService $encryption) {
    parent::__construct($config_factory);
    $this->encryption = $encryption;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('encryption')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'omeda_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['omeda.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('omeda.settings');

    $form['production_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Production API URL'),
      '#default_value' => $config->get('production_api_url') ? $this->encryption->decrypt($config->get('production_api_url'), TRUE) : '',
      '#required' => TRUE,
      '#description' => $this->t('This is the API url used when not in test mode.'),
    ];

    $form['testing_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Testing API URL'),
      '#default_value' => $config->get('testing_api_url') ? $this->encryption->decrypt($config->get('testing_api_url'), TRUE) : '',
      '#required' => TRUE,
      '#description' => $this->t('This is the API url used when in test mode.'),
    ];

    $form['api_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('API Mode'),
      '#options' => [
        'production' => $this->t('Production'),
        'testing' => $this->t('Testing'),
      ],
      '#default_value' => $config->get('api_mode'),
      '#required' => TRUE,
      '#description' => $this->t('This determines whether or not you are in testing mode.'),
    ];

    $form['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID / API Key'),
      '#default_value' => $config->get('app_id') ? $this->encryption->decrypt($config->get('app_id'), TRUE) : '',
      '#required' => TRUE,
      '#description' => $this->t('This is passed to the API as x-omeda-appid.'),
    ];

    $form['input_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input ID'),
      '#default_value' => $config->get('input_id') ? $this->encryption->decrypt($config->get('input_id'), TRUE) : '',
      '#required' => TRUE,
      '#description' => $this->t('This is passed to the API as x-omeda-inputid for update calls.'),
    ];

    $form['brand_abbreviation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Brand Abbreviation'),
      '#default_value' => $config->get('brand_abbreviation') ? $this->encryption->decrypt($config->get('brand_abbreviation'), TRUE) : '',
      '#required' => TRUE,
      '#description' => $this->t('This is passed as part of the URL for all API calls requiring it.'),
    ];

    $form['client_abbreviation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Abbreviation'),
      '#default_value' => $config->get('client_abbreviation') ? $this->encryption->decrypt($config->get('client_abbreviation'), TRUE) : '',
      '#required' => TRUE,
      '#description' => $this->t('This is passed as part of the URL for all API calls requiring it.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $encrypted_values = [
      'production_api_url' => $this->encryption->encrypt($form_state->getValue('production_api_url'), TRUE),
      'testing_api_url' => $this->encryption->encrypt($form_state->getValue('testing_api_url'), TRUE),
      'app_id' => $this->encryption->encrypt($form_state->getValue('app_id'), TRUE),
      'input_id' => $this->encryption->encrypt($form_state->getValue('input_id'), TRUE),
      'brand_abbreviation' => $this->encryption->encrypt($form_state->getValue('brand_abbreviation'), TRUE),
      'client_abbreviation' => $this->encryption->encrypt($form_state->getValue('client_abbreviation'), TRUE),
    ];
    $encryption_error = FALSE;

    foreach ($encrypted_values as $encrypted_value) {
      if (!$encrypted_value) {
        $encryption_error = TRUE;
        break;
      }
    }

    if ($encryption_error) {
      $this->messenger()->addError($this->t('Failed to encrypt values in the form. Please ensure that the Encryption module is enabled and that an encryption key is set.'));
    }
    else {
      $config = $this->config('omeda.settings');

      foreach ($encrypted_values as $key => $encrypted_value) {
        $config->set($key, $encrypted_value);
      }
      $config->set('api_mode', $form_state->getValue('api_mode'));

      $config->save();

      parent::submitForm($form, $form_state);
    }
  }

}
