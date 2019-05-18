<?php

namespace Drupal\aws_connector\Credentials;

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\Iot\Exception\IotException;
use Aws\Iot\IotClient;
use GuzzleHttp\Promise;

/**
 * Extend AWS\Credentials\CredentialProvider to provide AWS credentials.
 */
class AWSCredentialProvider extends CredentialProvider {

  /**
   * Extends AWS ini function by providing credentials out of Drupal
   * configuration instead of pulling from local .ini file in user's home
   * directory.
   *
   * @param string|null $profile
   *   Profile to use. If not specified will use the "default" profile.
   * @param string|null $filename
   *   Uses a custom filename rather than looking in the home directory.
   *
   * @return callable
   *   Credentials object.
   */
  public static function ini($profile = 'default', $filename = NULL) {

    return function () use ($profile, $filename) {
      $data[$profile] = self::getCredentials();
      $data[$profile]['aws_session_token'] = NULL;

      return Promise\promise_for(
        new Credentials(
          $data[$profile]['aws_access_key_id'],
          $data[$profile]['aws_secret_access_key'],
          $data[$profile]['aws_session_token']
        )
      );
    };
  }

  /**
   * Get credentials.
   *
   * @return array
   *   Credentials array.
   */
  public static function getCredentials() {
    global $config;
    $data = [];
    $aws_connector_config = \Drupal::config('aws_connector.settings');
    if ($aws_connector_config) {
      if (isset($config['aws_connector.aws_id'])) {
        $data['aws_access_key_id'] = $config['aws_connector.aws_id'];
      }
      else {
        $data['aws_access_key_id'] = self::getConfig('aws_id', $aws_connector_config);
      }

      if (isset($config['aws_connector.aws_id'])) {
        $data['aws_secret_access_key'] = $config['aws_connector.aws_secret'];
      }
      else {
        $data['aws_secret_access_key'] = self::getConfig('aws_secret', $aws_connector_config);
      }
      return $data;
    }
  }

  /**
   * Function that compares Drupal config and config overrides for the given
   * $key.
   *
   * @param string $key
   *   The key to be searched for in the $settings and configuration objects.
   * @param object $aws_connector_config
   *   The Drupal configuration object for this module.
   *
   * @return mixed
   *   The resulting value from the object.
   */
  public static function getConfig($key, $aws_connector_config) {
    global $config;
    if (isset($config['aws_connector.' . $key])) {
      return $config['aws_connector.' . $key];
    }

    return $aws_connector_config->get('aws_connector.' . $key);
  }

  /**
   * Validate credentials.
   *
   * @return array
   *   Credentials array.
   */
  public static function validateCredentials($aws_access_key_id, $aws_secret_access_key) {
    $profile = 'default';
    $data[$profile] = [
      'aws_access_key_id' => $aws_access_key_id,
      'aws_secret_access_key' => $aws_secret_access_key,
    ];
    $data[$profile]['aws_session_token'] = NULL;

    $a = new Credentials(
      $data[$profile]['aws_access_key_id'],
      $data[$profile]['aws_secret_access_key'],
      $data[$profile]['aws_session_token']
    );

    $client = new IotClient([
      'credentials' => $a,
      'region' => self::getRegion(),
      'version' => '2015-05-28',
    ]);

    $error_message = '';
    try {
      $endpoint = $client->describeEndpoint();
    }
    catch (IotException $e) {
      $error_message = t('Your credentials are invalid.');
    }

    return $error_message;

  }

  /**
   * Get endpoint.
   *
   * @return string
   *   Endpoint string.
   */
  public static function getEndpoint() {
    global $config;
    $aws_connector_config = \Drupal::config('aws_connector.settings');
    if ($aws_connector_config) {
      if (isset($config['aws_connector.aws_endpoint'])) {
        return $config['aws_connector.aws_endpoint'];
      }
      else {
        return self::getConfig('aws_endpoint', $aws_connector_config);
      }
    }
    return '';
  }

  /**
   * Get region.
   *
   * @return string
   *   Region string.
   */
  public static function getRegion() {
    global $config;
    $aws_connector_config = \Drupal::config('aws_connector.settings');
    if ($aws_connector_config) {
      if (isset($config['aws_connector.aws_region'])) {
        return $config['aws_connector.aws_region'];
      }
      else {
        return self::getConfig('aws_region', $aws_connector_config);
      }
    }
    return '';
  }

  /**
   * Get the AWS S3 bucket name.
   *
   * @return string
   *   AWS S3 bucket name.
   */
  public static function getS3Bucket() {
    global $config;
    $aws_connector_config = \Drupal::config('aws_connector.settings');
    if ($aws_connector_config) {
      if (isset($config['aws_connector.aws_s3_bucket'])) {
        return $config['aws_connector.aws_s3_bucket'];
      }
      else {
        return self::getConfig('aws_s3_bucket', $aws_connector_config);
      }
    }
    return '';
  }

}
