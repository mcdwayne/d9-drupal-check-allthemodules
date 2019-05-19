<?php

namespace Drupal\streamy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\PluginBase;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class StreamyStreamBase
 *
 * @package Drupal\streamy
 */
abstract class StreamyStreamBase extends PluginBase implements StreamyBasePluginInterface, StreamyStreamInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The current plugin configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @inheritdoc
   */
  public function setUp() {
    if (isset($this->pluginDefinition['configPrefix'])) {
      $this->config = $this->configFactory->get($this->pluginDefinition['configPrefix'] . '.' . $this->pluginId);
    }
  }

  /**
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
  public function getConfigFactory() {
    return $this->configFactory;
  }

  /**
   * Whether or not this Plugin can be a MASTER stream for whatever reason (performances, etc..).
   * On a particular slow Stream (e.g. Dropbox) you should set the return
   * of this method to FALSE.
   * This won't make available the current plugin as Master and generates
   * an error message in the streamy configuration.
   *
   * @return bool
   */
  abstract public function allowAsMasterStream();

  /**
   * Whether or not this Plugin can be a SLAVE stream.
   *
   * @return mixed
   */
  abstract public function allowAsSlaveStream();

  /**
   * @param $configFactory
   */
  public function setConfigFactory(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * @param $request
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * A shortcut to mount an adapter in a MountManager.
   *
   * @param $adapter
   * @param $mountName
   * @return \League\Flysystem\MountManager
   */
  protected function mountCurrentAdapter($adapter, $mountName) {
    $fileSystem = new Filesystem($adapter);
    $manager = new MountManager();
    $manager->mountFilesystem($mountName, $fileSystem);

    return $manager;
  }

  /**
   * @param            $e
   * @param            $scheme
   */
  protected function logEnsureException($e, $scheme) {
    if ($e instanceof \Error || $e instanceof \Exception) {
      $this->logger->error('This plugin has failed the ensure command with exception: %exception_type. Scheme: %scheme. Error message: %error_message',
                           ['%scheme' => $scheme, '%exception_type' => get_class($e), '%error_message' => $e->getMessage()]);
    }
  }

}
