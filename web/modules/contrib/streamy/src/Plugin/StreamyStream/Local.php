<?php

namespace Drupal\streamy\Plugin\StreamyStream;

use Drupal\Component\Utility\UrlHelper;
use Drupal\streamy\StreamyFormTrait;
use Drupal\streamy\StreamyStreamBase;
use League\Flysystem\Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use League\Flysystem\Adapter\Local as FlySystemLocal;

/**
 * Provides a 'StreamyStream' plugin.
 *
 * @StreamyStream(
 *   id = "local",
 *   name = @Translation("Local"),
 *   configPrefix = "streamy",
 *   description = @Translation("Provides a local stream.")
 * )
 */
class Local extends StreamyStreamBase {

  use StreamyFormTrait;

  /**
   * @inheritdoc
   */
  public function allowAsMasterStream() {
    return TRUE;
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
      'type'     => 'local',
      'settings' => [
        'root'        => rtrim($this->getPluginConfigurationValue('root', $scheme, $level, $pluginConfig), '/'),
        'slow_stream' => (bool) $this->getPluginConfigurationValue('slow_stream', $scheme, $level, $pluginConfig),
      ],
    ];
  }

  /**
   * @inheritdoc
   */
  public function ensure(string $scheme, string $level, array $config = []) {
    try {
      $adapter = $this->getAdapter($scheme, $level, $config);
      $fileSystem = new Filesystem($adapter);
      $manager = new MountManager([$scheme => $fileSystem]);
      $content = $manager->listContents($scheme . '://', FALSE);
    } catch (Exception $e) {
      $this->logEnsureException($e, $scheme);
      return FALSE;
    } catch (\Exception $e) {
      $this->logEnsureException($e, $scheme);
      return FALSE;
    } catch (\Throwable $t) {
      $this->logEnsureException($t, $scheme);
      return FALSE;
    }

    return $manager;
  }

  /**
   * @inheritdoc
   */
  public function getAdapter(string $scheme, string $level, array $config = []) {
    $settings = $this->getPluginSettings($scheme, $level, $config);

    $adapter = new FlySystemLocal($settings['settings']['root']);
    return $adapter;
  }

  /**
   * @inheritdoc
   */
  public function getExternalUrl($uri, string $scheme, string $level, MountManager $readFileSystem, $adapter) {
    $prefix = $scheme . '://';
    $settings = $this->getPluginSettings($scheme, $level);

    if ($settings['settings']['slow_stream'] !== TRUE) {
      if ($adapter instanceof FlySystemLocal) {
        $currentAdapterMount = $this->mountCurrentAdapter($adapter, $scheme);
        if ($currentAdapterMount->has($prefix . $uri)) {
          $path = str_replace('\\', '/', $settings['settings']['root'] . '/' . $uri);

          return $GLOBALS['base_url'] . '/' . UrlHelper::encodePath($path);
        }
      }
    }
    return NULL;
  }

}
