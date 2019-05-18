<?php

/**
 * @file
 * Contains \Drupal\flysystem_sftp\Flysystem\Sftp.
 */

namespace Drupal\flysystem_sftp\Flysystem;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\flysystem\Flysystem\Adapter\MissingAdapter;
use Drupal\flysystem\Plugin\FlysystemPluginInterface;
use Drupal\flysystem\Plugin\FlysystemUrlTrait;
use League\Flysystem\Sftp\SftpAdapter;

/**
 * Drupal plugin for the "SFTP" Flysystem adapter.
 *
 * @Adapter(id = "sftp")
 */
class Sftp implements FlysystemPluginInterface {

  use FlysystemUrlTrait;

  /**
   * Plugin configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Constructs an Sftp object.
   *
   * @param array $configuration
   *   Plugin configuration array.
   */
  public function __construct(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapter() {
    try {
      $adapter = new SftpAdapter($this->configuration);
      $adapter->connect();
    }

    catch (\Exception $e) {
      $adapter = new MissingAdapter();
    }

    return $adapter;
  }

  /**
   * {@inheritdoc}
   */
  public function ensure($force = FALSE) {
    try {
      $adapter = new SftpAdapter($this->configuration);
      $adapter->connect();
    }

    catch (\LogicException $e) {
      return [[
        'severity' => RfcLogLevel::ERROR,
        'message' => 'Unable to login to the SFTP server %host:%port with the provided credentials.',
        'context' => [
          '%host' => $this->configuration['host'],
          '%port' => isset($this->configuration['port']) ? $this->configuration['port'] : 22,
        ],
      ]];
    }

    catch (\RuntimeException $e) {
      return [[
        'severity' => RfcLogLevel::ERROR,
        'message' => 'The root %root is invalid or does not exist on the SFTP server %host:%port.',
        'context' => [
          '%root' => $this->configuration['root'],
          '%host' => $this->configuration['host'],
          '%port' => isset($this->configuration['port']) ? $this->configuration['port'] : 22,
        ],
      ]];
    }

    return [];
  }

}
