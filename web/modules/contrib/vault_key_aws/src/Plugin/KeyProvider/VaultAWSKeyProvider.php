<?php

namespace Drupal\vault_key_aws\Plugin\KeyProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Serialization\Json;
use Drupal\vault\VaultClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyPluginFormInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyProviderSettableValueInterface;

/**
 * Adds a key provider that fetches AWS credentials from HashiCorp Vault.
 *
 * @KeyProvider(
 *   id = "vault_aws",
 *   label = "Vault AWS",
 *   description = @Translation("This provider fetches AWS credentials from the HashiCorp Vault AWS secret engine."),
 *   storage_method = "vault_aws",
 *   key_value = {
 *     "accepted" = FALSE,
 *     "required" = FALSE
 *   }
 * )
 */
class VaultAWSKeyProvider extends KeyProviderBase implements KeyProviderSettableValueInterface, KeyPluginFormInterface {

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
   *   The secrets manager client.
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
      'secret_engine_mount' => 'aws/',
      'secret_path' => '',
    ];
  }

  /**
   * Returns the lease storage key for this provider.
   *
   * @param \Drupal\key\KeyInterface $key
   *   The key entity.
   *
   * @return string
   *   The storage key.
   */
  protected static function leaseStorageKey(KeyInterface $key) {
    return sprintf("key:%s", $key->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    // Check if there is a valid lease available.
    $lease_data = $this->client->retrieveLease(self::leaseStorageKey($key));
    if (!empty($lease_data)) {
      return $lease_data;
    }

    $this->logger->debug("no valid lease - reading new credentials for " . $key->id());
    $path = $this->buildRequestPath("get", $key);
    try {
      $response = $this->client->read($path);
      $data = Json::encode($response->getData());
      $this->client->storeLease(
        self::leaseStorageKey($key),
        $response->getLeaseId(),
        $data,
        $response->getLeaseDuration()
      );

      return $data;
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
    // There's nothing to do here - we only support reading aws credentials.
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKeyValue(KeyInterface $key) {
    // Revoke the lease.
    $this->logger->debug("key entity deletion triggered revocation of aws credential lease");
    $this->client->revokeLease(self::leaseStorageKey($key));
  }

  /**
   * {@inheritdoc}
   */
  public static function obscureKeyValue($key_value, array $options = []) {
    switch ($options['key_type_group']) {
      case 'authentication_multivalue':
        // Obscure the values of each element of the object to make it more
        // clear what the contents are.
        $options['visible_right'] = 4;

        $json = Json::decode($key_value);
        foreach ($json as $key => $value) {
          $json->{$key} = static::obscureKeyValue($key_value, $options);
        }
        $obscured_value = Json::encode($json);
        break;

      default:
        $obscured_value = parent::obscureKeyValue($key_value, $options);
    }

    return $obscured_value;
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
      '#default_value' => $provider_config['secret_engine_mount'],
      '#disabled' => !$new,
    ];

    try {
      // Attempt to provide better UX by listing the available mounts. If this
      // fails it will fall back to the standard textfied input.
      $mount_points = $client->listSecretEngineMounts(['aws']);

      $form['secret_engine_mount']['#type'] = 'select';
      $form['secret_engine_mount']['#options'] = [];
      foreach ($mount_points as $mount => $info) {
        $form['secret_engine_mount']['#options'][$mount] = $mount;
      }
    }
    catch (\Exception $e) {
      $this->logger->error("Unable to list mount points for key/value secret engine");
    }

    $form['secret_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Role Path'),
      '#description' => $this->t('The vault role path.'),
      '#default_value' => $provider_config['secret_path'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $key_provider_settings = $form_state->getValues();

    // Ensure secret path is only url safe characters.
    if (preg_match('/[^a-z_\-\/0-9]/i', $key_provider_settings['secret_path'])) {
      $form_state->setErrorByName('secret_path', $this->t('Secret Path Prefix only supports the following characters: a-z 0-9 . - _ /'));
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
   *   The action being performed.
   * @param \Drupal\key\KeyInterface $key
   *   The key entity.
   *
   * @return string
   *   Request path for desired vault endpoint.
   */
  protected function buildRequestPath(string $action, KeyInterface $key) {
    $provider_config = $this->getConfiguration();

    switch ($action) {
      case 'get':
        $url = new FormattableMarkup("/:secret_engine_mount:endpoint/:secret_path", [
          ':secret_engine_mount' => $provider_config['secret_engine_mount'],
          ':endpoint' => 'creds',
          ':secret_path' => $provider_config['secret_path'],
        ]);
        return (string) $url;

      default:
        return '';
    }

  }

}
