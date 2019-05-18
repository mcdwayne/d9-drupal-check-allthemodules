<?php

namespace Drupal\aws_secrets_manager\Plugin\KeyProvider;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Aws\SecretsManager\SecretsManagerClient;
use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyProviderSettableValueInterface;

/**
 * Adds a key provider that allows a key to be stored in AWS Secrets Manager.
 *
 * @KeyProvider(
 *   id = "aws_secrets_manager",
 *   label = "AWS Secrets Manager",
 *   description = @Translation("This provider stores the key in AWS Secrets Manager."),
 *   storage_method = "aws_secrets_manager",
 *   key_value = {
 *     "accepted" = TRUE,
 *     "required" = TRUE
 *   }
 * )
 */
class AwsSecretsManagerKeyProvider extends KeyProviderBase implements KeyProviderSettableValueInterface {

  /**
   * The settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The KMS client.
   *
   * @var \Aws\SecretsManager\SecretsManagerClient
   */
  protected $client;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
    $instance->configFactory = \Drupal::configFactory();
    return $instance
      ->setClient($container->get('aws_secrets_manager.aws_secrets_manager_client'))
      ->setLogger($container->get('logger.channel.aws_secrets_manager'));
  }

  /**
   * Sets kmsClient property.
   *
   * @param \Aws\SecretsManager\SecretsManagerClient $client
   *   The secrets manager client.
   *
   * @return self
   *   Current object.
   */
  public function setClient(SecretsManagerClient $client) {
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    $name = $key->id();

    try {
      $response = $this->client->getSecretValue([
        "SecretId" => $this->secretName($name),
      ]);

      if ($value = $response->get('SecretString')) {
        return $value;
      }
    }
    catch (\Exception $e) {
      $this->logger->error(sprintf("unable to retrieve secret %s", $name));
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyValue(KeyInterface $key, $key_value) {
    $name = $key->id();
    $label = $key->label();

    try {
      $response = $this->client->createSecret([
        "Name" => $this->secretName($name),
        "Description" => $label,
        "SecretString" => $key_value,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error(sprintf("unable to create secret %s", $name));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKeyValue(KeyInterface $key) {
    $name = $key->id();

    try {
      $response = $this->client->deleteSecret([
        "SecretId" => $this->secretName($name),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error(sprintf("unable to delete secret %s", $name));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Generates a prefixed secret name.
   *
   * @param string $key_name
   *   The key machine name.
   *
   * @return string
   *   The secret name as stored in AWS.
   */
  public function secretName($key_name) {
    $config = $this->configFactory->get('aws_secrets_manager.settings');
    $config->get('secret_prefix');
    $parts = [
      $config->get('secret_prefix'),
      $key_name,
    ];
    return implode("-", array_filter($parts));
  }

}
