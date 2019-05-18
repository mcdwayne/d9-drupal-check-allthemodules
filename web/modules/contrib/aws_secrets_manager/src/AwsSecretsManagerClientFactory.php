<?php

namespace Drupal\aws_secrets_manager;

use Aws\SecretsManager\SecretsManagerClient;
use Drupal\Core\Config\ConfigFactory;

/**
 * Factory class for SecretsManagerClient which uses credentials in config.
 *
 * @package Drupal\aws_secrets_manager
 */
class AwsSecretsManagerClientFactory {

  /**
   * Creates an AWS Secrets Manager Client instance.
   *
   * @param array $options
   *   The default client options.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   *
   * @return \Aws\SecretsManager\SecretsManagerClient
   *   The client.
   */
  public static function createInstance(array $options, ConfigFactory $configFactory) {
    $settings = $configFactory->get('aws_secrets_manager.settings');
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
    return new SecretsManagerClient($options);
  }

}
