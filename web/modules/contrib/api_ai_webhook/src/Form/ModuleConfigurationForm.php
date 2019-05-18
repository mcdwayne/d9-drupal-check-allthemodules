<?php

namespace Drupal\api_ai_webhook\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ModuleConfigurationForm.
 *
 * @package Drupal\api_ai_webhook\Form
 */
class ModuleConfigurationForm extends ConfigFormBase {

  /**
   * State Manager.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $stateManager) {
    parent::__construct($config_factory);
    $this->stateManager = $stateManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'api_ai_webhook.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'api_ai_webhook_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('api_ai_webhook.settings');
    $state_data = $this->stateManager->get('api_ai_webhook.auth', []);

    $types = ['none', 'basic', 'headers'];
    $form['type'] = [
      '#type' => 'radios',
      '#title' => 'Authentication type',
      '#required' => TRUE,
      '#options' => array_combine($types, $types),
      '#default_value' => $config->get('auth.type') ?: 'none',
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Basic Authentication Username'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('auth.values.username'),
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 'basic'],
        ],
        'required' => [
          ':input[name="type"]' => ['value' => 'basic'],
        ],
      ],
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Basic Authentication password'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => isset($state_data['password']) ? $state_data['password'] : '',
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 'basic'],
        ],
        'required' => [
          ':input[name="type"]' => ['value' => 'basic'],
        ],
      ],
    ];

    // Build default_value for headers.
    $headers = '';
    if (isset($state_data['headers']) && is_array($state_data['headers'])) {
      foreach ($state_data['headers'] as $key => $value) {
        $headers .= $key . ':' . $value . PHP_EOL;
      }
    }
    $form['http_headers'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Headers'),
      '#description' => $this->t('Custom authentication headers'),
      '#default_value' => $headers,
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 'headers'],
        ],
        'required' => [
          ':input[name="type"]' => ['value' => 'headers'],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    switch ($form_state->getValue('type')) {
      case 'basic':
        // Username is required.
        if (!trim($form_state->getValue('username'))) {
          $form_state->setErrorByName('username', 'Username is required for Basic type');
        }

        if ($state_data = $this->stateManager->get('api_ai_webhook.auth', [])) {
          if (!isset($state_data['password']) && !trim($form_state->getValue('password'))) {
            $form_state->setErrorByName('password', 'Password is required for Basic type');
          }
        }
        break;

      case 'headers':
        if (!trim($form_state->getValue('http_headers'))) {
          $form_state->setErrorByName('http_headers', 'Headers key/value are required type');
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('api_ai_webhook.settings');
    $type = $form_state->getValue('type');
    $state_data = $this->stateManager->get('api_ai_webhook.auth', []);

    $config->set('auth.type', $type);
    $config->set('auth.values', []);
    switch ($type) {
      case 'basic':
        unset($state_data['headers']);
        $config->set('auth.values.username', $form_state->getValue('username'));

        // We can't store password in the configuration!
        $config->set('auth.values.password', 'xxxxx');
        if ($password = $form_state->getValue('password')) {
          $state_data['password'] = Crypt::hmacBase64($form_state->getValue('password'), Settings::getHashSalt());
        }
        break;

      case 'headers':
        unset($state_data['password']);
        $headers = [];
        $line = strtok($form_state->getValue('http_headers'), "\r\n");
        while ($line) {
          $header = explode(':', $line);
          $headers[$header[0]] = $header[1];
          $line = strtok("\r\n");
        }

        // We can't store headers values in the config.
        $config->set('auth.values', array_keys($headers));
        $state_data['headers'] = $headers;
        break;

      default:
        $state_data = [];
    }

    $this->stateManager->set('api_ai_webhook.auth', $state_data);
    $config->save();
  }

}
