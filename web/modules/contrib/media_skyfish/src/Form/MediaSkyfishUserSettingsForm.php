<?php

namespace Drupal\media_skyfish\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_skyfish\ApiService;
use Drupal\media_skyfish\ConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MediaSkyfishUserSettingsForm.
 */
class MediaSkyfishUserSettingsForm extends ConfigFormBase {

  /**
   * Base url for service.
   */
  public const API_BASE_URL = 'https://api.colourbox.com';

  /**
   * Skyfish config service.
   *
   * @var \Drupal\media_skyfish\ConfigService
   */
  protected $config;

  /**
   * Skyfish api service.
   *
   * @var \Drupal\media_skyfish\ApiService
   */
  protected $connect;

  /**
   * Current user entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;


  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, ConfigService $config, ApiService $connect) {
    parent::__construct($config_factory);
    $this->config = $config;
    $this->connect = $connect;
    $this->user = $entity_type_manager->getStorage('user')->load($this->currentUser()->id());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('media_skyfish.configservice'),
      $container->get('media_skyfish.apiservice')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // Do not save user sensitive configuration
    // in any kind if exportable configs, so we return empty config array.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_skyfish_user_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['skyfish_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skyfish Username'),
      '#description' => $this->t('Please enter username to login to Skyfish.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $this->user->field_skyfish_username->value,
    ];
    $form['skyfish_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Skyfish Password'),
      '#description' => $this->t('Please enter password to login to Skyfish.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $this->user->field_skyfish_password->value,
    ];
    $form['skyfish_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skyfish API Key'),
      '#description' => $this->t('Please enter Skyfish API Key here.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $this->user->field_skyfish_api_user->value,
    ];
    $form['skyfish_api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skyfish API Secret'),
      '#description' => $this->t('Please enter Skyfish API secret key.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $this->user->field_skyfish_secret_api_key->value,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Set input values temporarily for the ConfigService.
    $this->config->setKey($form_state->getValue('skyfish_api_key'));
    $this->config->setSecret($form_state->getValue('skyfish_api_secret'));
    $this->config->setUsername($form_state->getValue('skyfish_user'));
    $this->config->setPassword($form_state->getValue('skyfish_password'));

    // Check if using input values it is possible to authorize with Skyfish API.
    if (!$this->connect->getToken()) {
      $form_state->setError($form, 'Incorrect login information.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();

    if ($this->user) {
      $this->user->set('field_skyfish_api_user', $values['skyfish_api_key']);
      $this->user->set('field_skyfish_secret_api_key', $values['skyfish_api_secret']);
      $this->user->set('field_skyfish_username', $values['skyfish_user']);
      $this->user->set('field_skyfish_password', $values['skyfish_password']);
      $this->user->save();
    }
  }
}
