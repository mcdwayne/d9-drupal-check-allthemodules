<?php

namespace Drupal\streamy_dropbox\Plugin\StreamyStream;

use Dropbox\Client;
use Drupal\streamy\StreamyFormTrait;
use Drupal\streamy\StreamyStreamBase;
use League\Flysystem\Dropbox\DropboxAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;

/**
 * Provides a 'StreamyStream' plugin.
 *
 * @StreamyStream(
 *   id = "dropbox",
 *   name = @Translation("Dropbox"),
 *   configPrefix = "streamy_dropbox",
 *   description = @Translation("Provides a Dropbox stream.")
 * )
 */
class Dropbox extends StreamyStreamBase {

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
      'type'     => 'dropbox',
      'settings' => [
        'accesstoken' => $this->getPluginConfigurationValue('accesstoken', $scheme, $level, $pluginConfig),
        'secret'      => $this->getPluginConfigurationValue('secret', $scheme, $level, $pluginConfig),
        'prefix'      => $this->getPluginConfigurationValue('prefix', $scheme, $level, $pluginConfig),
        'slow_stream' => (bool) $this->getPluginConfigurationValue('slow_stream', $scheme, $level, $pluginConfig),
      ],
    ];
  }

  /**
   * @inheritdoc
   */
  public function getAdapter(string $scheme, string $level, array $config = []) {
    $settings = $this->getPluginSettings($scheme, $level, $config);

    $client = new Client($settings['settings']['accesstoken'],
                         $settings['settings']['secret']);
    $adapter = new DropboxAdapter($client, $settings['settings']['prefix']);
    return $adapter;
  }

  /**
   * @inheritdoc
   */
  public function getExternalUrl($uri, string $scheme, string $level, MountManager $readFileSystem, $adapter) {
    $prefix = $scheme . '://';
    $settings = $this->getPluginSettings($scheme, $level);

    if ($settings['settings']['slow_stream'] !== TRUE) {
      if ($adapter instanceof DropboxAdapter) {
        $currentAdapterMount = $this->mountCurrentAdapter($adapter, $scheme);
        if ($currentAdapterMount->has($prefix . $uri)) {

          $client = new Client($settings['settings']['accesstoken'],
                               $settings['settings']['secret']);

          // This is not recommended because generates a random URL every time the
          // old URL expires.
          $url = $client->createTemporaryDirectLink('/' . ltrim($uri, '/'));

          return $url && isset($url[0]) ? $url[0] : NULL;
        }
      }
    }
    return NULL;
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
