<?php

namespace Drupal\mattermost_integration\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MattermostIntegrationConfigurationForm Global configuration form.
 *
 * @package Drupal\mattermost_integration\Form
 */
class MattermostIntegrationConfigurationForm extends ConfigFormBase {

  /**
   * The State.
   *
   * @var State
   */
  protected $state;

  /**
   * MattermostIntegrationConfigurationForm constructor.
   *
   * @param StateInterface $state
   *   The State.
   * @param ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mattermost_integration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mattermost_integration.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mattermost_integration.settings');

    $form['api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Mattermost server URL'),
      '#description' => $this->t('Enter your Mattermost server URL where the API can be found.'),
      '#default_value' => $config->get('api_url'),
      '#required' => TRUE,
      '#field_suffix' => '/api/v3/',
    ];
    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Credentials'),
    ];
    $form['credentials']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
      '#default_value' => $config->get('credentials.username'),
    ];
    $form['credentials']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => empty($config->get('credentials.password')) ?: FALSE,
      '#description' => $this->t('Leave empty to keep current password.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if (preg_match('/^http?:\/\//', $values['api_url'])) {
      drupal_set_message($this->t('Warning: you are using an insecure connection for your Mattermost server (HTTP). It is strongly recommended to use a secured connection (HTTPS)!'), 'warning');
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @TODO: Encrypt the Mattermost authentication token instead of storing it as
   *        a state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('mattermost_integration.settings')
      ->set('api_url', $values['api_url'])
      ->set('credentials.username', $form_state->getValue('username'))
      ->save();

    if (!empty($values['password'])) {
      $this->config('mattermost_integration.settings')
        ->set('credentials.password', $form_state->getValue('password'))
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
