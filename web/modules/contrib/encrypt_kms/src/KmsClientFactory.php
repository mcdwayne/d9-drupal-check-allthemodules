<?php

namespace Drupal\encrypt_kms;

use Aws\Kms\KmsClient;
use Drupal\Core\Config\ConfigFactory;

/**
 * Factory class for KmsClient which checks for credentials in config.
 *
 * @package Drupal\encrypt_kms
 */
class KmsClientFactory {

  /**
   * Creates an AWS KMS Client instance.
   *
   * @param array $options
   *   The default client options.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   *
   * @return \Aws\Kms\KmsClient
   *   The client.
   */
  public static function createInstance(array $options, ConfigFactory $configFactory) {
    $settings = $configFactory->get('encrypt_kms.settings');
    $awsKey = $settings->get('aws_key');
    $awsSecret = $settings->get('aws_secret');
    $awsRegion = $settings->get('aws_region');

    // Pass in credentials if they are set.
    if (!empty($awsKey) && !empty($awsSecret)) {
      $options['credentials'] = [
        'key' => $awsKey,
        'secret' => $awsSecret,
      ];
    }

    $options['region'] = $awsRegion;
    $options['version'] = 'latest';
    return new KmsClient($options);
  }

}
