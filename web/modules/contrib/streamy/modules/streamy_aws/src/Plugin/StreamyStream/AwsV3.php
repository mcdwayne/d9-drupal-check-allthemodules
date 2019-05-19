<?php

namespace Drupal\streamy_aws\Plugin\StreamyStream;

use Aws\S3\S3Client;
use Drupal\streamy\StreamyFormTrait;
use Drupal\streamy\StreamyStreamBase;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;

/**
 * Provides a 'StreamyStream' plugin.
 *
 * @StreamyStream(
 *   id = "awsv3",
 *   name = @Translation("Aws S3"),
 *   configPrefix = "streamy_aws",
 *   description = @Translation("Provides an Aws S3 stream.")
 * )
 */
class AwsV3 extends StreamyStreamBase {

  use StreamyFormTrait;

  /**
   * @inheritdoc
   */
  public function allowAsMasterStream() {
    return FALSE;
  }

  /**
   * @inheritdoc
   */
  public function allowAsSlaveStream() {
    return TRUE;
  }

  /**
   * @inheritdoc
   */
  public function getPluginSettings(string $scheme, string $level, array $config = []) {
    $pluginConfig = $config ? $config : (array) $this->config->get('plugin_configuration');
    return [
      'type'     => 'awsv3',
      'settings' => [
        'aws-settings' => [
          'credentials' => [
            'key'    => $this->getPluginConfigurationValue('aws_key', $scheme, $level, $pluginConfig),
            'secret' => $this->getPluginConfigurationValue('aws_secret', $scheme, $level, $pluginConfig),
          ],
          'region'      => $this->getPluginConfigurationValue('aws_region', $scheme, $level, $pluginConfig),
          'version'     => '2006-03-01',
        ],
        'bucket'       => [
          'name'   => rtrim($this->getPluginConfigurationValue('aws_bucket', $scheme, $level, $pluginConfig), '/'),
          'prefix' => rtrim($this->getPluginConfigurationValue('aws_prefix', $scheme, $level, $pluginConfig), '/'),
        ],
        'slow_stream'  => (bool) $this->getPluginConfigurationValue('slow_stream', $scheme, $level, $pluginConfig),
      ],
    ];
  }

  /**
   * @inheritdoc
   */
  public function getAdapter(string $scheme, string $level, array $config = []) {
    $settings = $this->getPluginSettings($scheme, $level, $config);

    $client = S3Client::factory($settings['settings']['aws-settings']);
    $adapter = new AwsS3Adapter($client,
                                $settings['settings']['bucket']['name'],
                                $settings['settings']['bucket']['prefix']);
    return $adapter;
  }

  /**
   * @inheritdoc
   */
  public function getExternalUrl($uri, string $scheme, string $level, MountManager $readFileSystem, $adapter) {
    $url = NULL;
    $prefix = $scheme . '://';
    $settings = $this->getPluginSettings($scheme, $level);

    if ($settings['settings']['slow_stream'] !== TRUE) {
      if ($adapter instanceof AwsS3Adapter) {
        $currentAdapterMount = $this->mountCurrentAdapter($adapter, $scheme);
        if ($currentAdapterMount->has($prefix . $uri)) {
          $client = $adapter->getClient();
          $url = $client->getObjectUrl($settings['settings']['bucket']['name'], $uri);
        }
      }
    }
    return $url;
  }

  /**
   * @inheritdoc
   */
  public function ensure(string $scheme, string $level, array $config = []) {
    try {
      $fileSystem = new Filesystem($this->getAdapter($scheme, $level, $config));
      $manager = new MountManager([$scheme => $fileSystem]);
      $manager->listContents($scheme . '://', FALSE);
    } catch (\Exception $e) {
      $this->logEnsureException($e, $scheme);
      return FALSE;
    } catch (\Throwable $t) {
      $this->logEnsureException($t, $scheme);
      return FALSE;
    }

    return $manager;
  }

}
