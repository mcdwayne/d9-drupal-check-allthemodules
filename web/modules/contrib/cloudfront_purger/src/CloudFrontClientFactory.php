<?php

namespace Drupal\cloudfront_purger;

use Aws\CloudFront\CloudFrontClient;
use Drupal\Core\Config\ConfigFactory;

/**
 * Factory class for CloudFrontClient which checks for credentials in config.
 *
 * @package Drupal\cloudfront_purger
 */
class CloudFrontClientFactory {

  /**
   * Creates an AWS CloudFront Client instance.
   *
   * @param array $options
   *   The default client options.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   *
   * @return \Aws\CloudFront\CloudFrontClient
   *   The client.
   */
  public static function createInstance(array $options, ConfigFactory $configFactory) {
    $settings = $configFactory->get('cloudfront_purger.settings');
    $awsKey = $settings->get('aws_key');
    $awsSecret = $settings->get('aws_secret');

    // Pass in credentials if they are set.
    if (!empty($awsKey) && !empty($awsSecret)) {
      $options['credentials'] = [
        'key' => $awsKey,
        'secret' => $awsSecret,
      ];
    }
    return new CloudFrontClient($options);
  }

}
