<?php

namespace Drupal\hellosign\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\encryption\EncryptionService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure HelloSign settings for this site.
 */
class HellosignSettingsForm extends ConfigFormBase {

  /**
   * The encryption service.
   *
   * @var \Drupal\encryption\EncryptionService
   */
  protected $encryption;

  /**
   * Constructs a \Drupal\hellosign\Form\HellosignSettingsForm object.
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
    return 'hellosign_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hellosign.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hellosign.settings');

    $form['hellosign_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HelloSign API Key'),
      '#default_value' => $config->get('api_key') ? $this->encryption->decrypt($config->get('api_key'), TRUE) : '',
    ];
    $form['hellosign_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HelloSign Client ID'),
      '#default_value' => $config->get('client_id') ? $this->encryption->decrypt($config->get('client_id'), TRUE) : '',
    ];
    $form['hellosign_cc_emails'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CC email addresses'),
      '#description' => $this->t('Email addresses that will be copied on all requests, but will not have a signer role. Separate multiple email addresses with a comma.'),
      '#default_value' => $config->get('cc_emails'),
    ];
    $form['hellosign_test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test mode'),
      '#default_value' => $config->get('test_mode'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $encrypted_api_key = $this->encryption->encrypt($form_state->getValue('hellosign_api_key'), TRUE);
    $encrypted_client_id = $this->encryption->encrypt($form_state->getValue('hellosign_client_id'), TRUE);

    if (is_null($encrypted_api_key) || is_null($encrypted_client_id)) {
      drupal_set_message($this->t('Failed to encrypt the API Key and/or Client ID. Please ensure that the Encryption module is enabled and that an encryption key has been set.'), 'error');
    }

    $this->config('hellosign.settings')
      ->set('api_key', $encrypted_api_key)
      ->set('client_id', $encrypted_client_id)
      ->set('cc_emails', $form_state->getValue('hellosign_cc_emails'))
      ->set('test_mode', $form_state->getValue('hellosign_test_mode'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
