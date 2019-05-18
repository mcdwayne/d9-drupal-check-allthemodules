<?php

namespace Drupal\encrypt_vault_transit\Plugin\EncryptionMethod;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\encrypt\EncryptionMethodInterface;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;
use Drupal\vault\VaultClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AwsKmsEncryptionMethod.
 *
 * @EncryptionMethod(
 *   id = "vault_transit",
 *   title = @Translation("Vault Transit"),
 *   description = "Encryption using Vault Transit secret backend",
 *   key_type = {"vault_transit"}
 * )
 */
class VaultTransitEncryptionMethod extends EncryptionMethodBase implements EncryptionMethodInterface, ContainerFactoryPluginInterface {

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
  protected $vaultClient;

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
      ->setVaultClient($container->get('vault.vault_client'))
      ->setLogger($container->get('logger.channel.vault'));
  }

  /**
   * Sets VaultClient property.
   *
   * @param \Drupal\Vault\VaultClient $vaultClient
   *   The KMS client.
   *
   * @return self
   *   Current object.
   */
  public function setVaultClient(VaultClient $vaultClient) {
    $this->vaultClient = $vaultClient;
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
  public function checkDependencies($text = NULL, $key = NULL) {
    $errors = [];

    if (!class_exists('\Drupal\Vault\VaultClient')) {
      $errors[] = $error = $this->t('HashiCorp Vault library is not correctly installed.');
      $this->logger->error($error);
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt($text, $key, $options = []) {
    try {
      $data = [
        'name' => $key,
        'plaintext' => base64_encode($text),
      ];
      $response = $this->vaultClient->write(sprintf("/transit/encrypt/%s", $key), $data);
      return $response->getData()['ciphertext'];
    }
    catch (\Exception $e) {
      $this->logException($e, $text);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($text, $key, $options = []) {
    try {
      $data = [
        'name' => $key,
        'ciphertext' => $text,
      ];
      $response = $this->vaultClient->write(sprintf("/transit/decrypt/%s", $key), $data);
      $data = $response->getData();
      if (!empty($data['plaintext'])) {
        return base64_decode($data['plaintext']);
      }
      throw new \Exception('unexpected response when decrypting ' . $key);
    }
    catch (\Exception $e) {
      $this->logException($e, $text);
      return FALSE;
    }
  }

  /**
   * Helper method for logging exceptions.
   *
   * @param \Exception $e
   *   The exception.
   * @param string $plaintext
   *   Plaintext data to redact from logs.
   */
  public function logException(\Exception $e, $plaintext) {
    $placeholder = '**REDACTED**';
    $message = str_replace($plaintext, $placeholder, $e->getMessage());
    $context['sensitive_data'] = $plaintext;
    $this->logger->error($message, $context);
  }

}
