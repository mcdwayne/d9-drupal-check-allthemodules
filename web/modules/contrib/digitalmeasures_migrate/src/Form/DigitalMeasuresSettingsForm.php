<?php

namespace Drupal\digitalmeasures_migrate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class DigitalMeasuresSettingsForm.
 */
class DigitalMeasuresSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The configuration key for settings.
   */
  const CONF_ID = 'digitalmeasures_migrate.settings';

  /**
   * Constructs a new DigitalMeasuresSettingsForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'digital_measures_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      static::CONF_ID,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get(static::CONF_ID);

    $api_endpoint = $config->get('api_endpoint');
    if (empty($api_endpoint)) {
      $api_endpoint = 'test_v4';
    }

    $form['api_endpoint'] = [
      '#type' => 'select',
      '#title' => $this->t('API Endpoint'),
      '#options' => [
        'prod_v4' => $this->t('Production (v4)'),
        'test_v4' => $this->t('Testing (v4)'),
      ],
      '#description' => $this->t('The URL of the Digital Measures API endpoint to use.'),
      '#weight' => '0',
      '#default_value' => $api_endpoint,
      '#required' => TRUE,
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('The username with which to access the Digital Measures API.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#default_value' => $config->get('username'),
    ];

    $form['password'] = [
      '#type' => 'password',
      '#description' => $this->t('The password with which to access the Digital Measures API.'),
      '#title' => $this->t('Password'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#default_value' => $config->get('password'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config(static::CONF_ID)
      ->set('api_endpoint', $form_state->getValue('api_endpoint'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->save();
  }

}
