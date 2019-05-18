<?php

namespace Drupal\encrypt_kms\Plugin\EncryptionMethod;

use Drupal\encrypt\EncryptionMethodInterface;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;
use Aws\Kms\KmsClient;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AwsKmsEncryptionMethod.
 *
 * @EncryptionMethod(
 *   id = "aws_kms",
 *   title = @Translation("Amazon KMS"),
 *   description = "Encryption using Amazon KMS",
 *   key_type = {"aws_kms"}
 * )
 */
class AwsKmsEncryptionMethod extends EncryptionMethodBase implements EncryptionMethodInterface, ContainerFactoryPluginInterface {

  /**
   * The settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The KMS client.
   *
   * @var \Aws\Kms\KmsClient
   */
  protected $kmsClient;

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
      ->setKmsClient($container->get('encrypt_kms.kms_client'))
      ->setLogger($container->get('logger.channel.encrypt_kms'));
  }

  /**
   * Sets kmsClient property.
   *
   * @param \Aws\Kms\KmsClient $kmsClient
   *   The KMS client.
   *
   * @return self
   *   Current object.
   */
  public function setKmsClient(KmsClient $kmsClient) {
    $this->kmsClient = $kmsClient;
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

    if (!class_exists('\Aws\Kms\KmsClient')) {
      $errors[] = $error = $this->t('AWS KMS PHP library is not correctly installed.');
      $this->logger->error($error);
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt($text, $key, $options = []) {
    try {
      $result = $this->kmsClient->encrypt([
        'KeyId' => $key,
        'Plaintext' => $text,
      ]);

      return $result['CiphertextBlob'];
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
      $result = $this->kmsClient->decrypt([
        'KeyId' => $key,
        'CiphertextBlob' => $text,
      ]);

      return $result['Plaintext'];
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
