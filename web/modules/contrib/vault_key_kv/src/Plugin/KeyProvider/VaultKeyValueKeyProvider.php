<?php

namespace Drupal\vault_key_kv\Plugin\KeyProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\vault\VaultClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyPluginFormInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyProviderSettableValueInterface;
use Psr\Log\LoggerInterface;

/**
 * Adds a key provider that allows a key to be stored in HashiCorp Vault.
 *
 * @KeyProvider(
 *   id = "vault_kv",
 *   label = "Vault Key/Value",
 *   description = @Translation("This provider stores the key in HashiCorp Vault key/value secret engine."),
 *   storage_method = "vault_kv",
 *   key_value = {
 *     "accepted" = TRUE,
 *     "required" = TRUE
 *   }
 * )
 */
class VaultKeyValueKeyProvider extends KeyProviderBase implements KeyProviderSettableValueInterface, KeyPluginFormInterface {

  /**
   * The settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The Vault client.
   *
   * @var \Drupal\vault\VaultClient
   */
  protected $client;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return $instance
      ->setClient($container->get('vault.vault_client'))
      ->setLogger($container->get('logger.channel.vault'));
  }

  /**
   * Sets client property.
   *
   * @param \Drupal\vault\VaultClient $client
   *   The vault client.
   *
   * @return self
   *   Current object.
   */
  public function setClient(VaultClient $client) {
    $this->client = $client;
    return $this;
  }

  /**
   * Sets logger property.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   *
   * @return self
   *   Current object.
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'secret_engine_mount' => 'secret/',
      'secret_path_prefix' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    $path = $this->buildRequestPath("get", $key);
    try {
      $response = $this->client->read($path);
      $data = $response->getData()['data'];
      return isset($data['value']) ? $data['value'] : '';
    }
    catch (\Exception $e) {
      $this->logger->critical('Unable to fetch secret ' . $key->id());
      return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyValue(KeyInterface $key, $key_value) {
    $path = $this->buildRequestPath("set", $key);
    try {
      $response = $this->client->write($path, ['data' => ['value' => $key_value]]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->critical('Unable to write secret ' . $key->id());
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKeyValue(KeyInterface $key) {
    $path = $this->buildRequestPath("delete", $key);
    try {
      $response = $this->client->delete($path);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->critical('Unable to delete secret ' . $key->id());
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $client = \Drupal::service('vault.vault_client');
    $vault_config = \Drupal::config('vault.settings');
    $provider_config = $this->getConfiguration();
    $new = empty($form_state->getStorage()['key_value']['current']);

    $form['secret_engine_mount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Engine Mount'),
      '#description' => $this->t('The Key/Value secret engine mount point.'),
      '#field_prefix' => sprintf('%s/%s/', $vault_config->get('base_url'), $client::API),
      '#required' => TRUE,
      '#default_value' => $provider_config['secret_engine_mount'],
      '#disabled' => !$new,
    ];

    $form['secret_path_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Path Prefix'),
      '#description' => $this->t('The path prefix where the secret should be stored. The key machine name will be appended to this value. There may be policy restrictions at certain paths.'),
      '#default_value' => $provider_config['secret_path_prefix'],
      '#disabled' => !$new,
    ];

    try {
      // Attempt to provide better UX by listing the available mounts. If this
      // fails it will fall back to the standard textfied input.
      $mount_points = $client->listSecretEngineMounts(['kv']);

      $form['secret_engine_mount']['#type'] = 'select';
      $form['secret_engine_mount']['#options'] = [];
      foreach ($mount_points as $mount => $info) {
        $form['secret_engine_mount']['#options'][$mount] = $mount;
      }
    }
    catch (\Exception $e) {
      $this->logger->error("Unable to list mount points for key/value secret engine");
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $key_provider_settings = $form_state->getValues();

    // Ensure secret path is only url safe characters.
    if (preg_match('/[^a-z_\-\/0-9]/i', $key_provider_settings['secret_path_prefix'])) {
      $form_state->setErrorByName('secret_path_prefix', $this->t('Secret Path Prefix only supports the following characters: a-z 0-9 . - _ /'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * Builds the URL endpoint.
   *
   * @param string $action
   *   Action being performed. One of "get", "set", "delete".
   * @param \Drupal\key\KeyInterface $key
   *   Key entity.
   *
   * @return string
   *   Endpoint URL for request action.
   */
  protected function buildRequestPath(string $action, KeyInterface $key) {
    $provider_config = $this->getConfiguration();

    $placeholders = [
      ':secret_engine_mount' => $provider_config['secret_engine_mount'],
      ':endpoint' => '',
      ':secret_path' => implode('/', array_filter([
        trim($provider_config['secret_path_prefix'], '/'),
        $key->id(),
      ])),
    ];
    switch ($action) {
      case 'get':
      case 'set':
        $placeholders[':endpoint'] = 'data';
        break;

      case 'delete':
        $placeholders[':endpoint'] = 'metadata';
        break;
    }
    $url = new FormattableMarkup("/:secret_engine_mount:endpoint/:secret_path", $placeholders);
    return (string) $url;
  }

}
