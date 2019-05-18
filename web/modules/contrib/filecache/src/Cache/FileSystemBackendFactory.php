<?php

namespace Drupal\filecache\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;

/**
 * Factory for creating FileSystemBackend cache backends.
 */
class FileSystemBackendFactory implements CacheFactoryInterface {

  /**
   * The service for interacting with the file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The environment specific settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The cache tags checksum provider.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $checksumProvider;

  /**
   * Constructs a FileSystemBackendFactory object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The service for interacting with the file system.
   * @param \Drupal\Core\Site\Settings $settings
   *   The environment specific settings.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksumProvider
   *   The cache tags checksum provider.
   */
  public function __construct(FileSystemInterface $fileSystem, Settings $settings, TimeInterface $time, CacheTagsChecksumInterface $checksumProvider) {
    $this->fileSystem = $fileSystem;
    $this->settings = $settings;
    $this->time = $time;
    $this->checksumProvider = $checksumProvider;
  }

  /**
   * Returns the FileSystemBackend for the specified cache bin.
   *
   * @param string $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\filecache\Cache\FileSystemBackend
   *   The cache backend object for the specified cache bin.
   *
   * @throws \Exception
   *   Thrown when no path has been configured to store the files for the given
   *   bin.
   */
  public function get($bin) {
    $path = $this->getPathForBin($bin);
    $strategy = $this->getCacheStrategyForBin($bin);
    return new FileSystemBackend($this->fileSystem, $this->time, $this->checksumProvider, $path, $strategy);
  }

  /**
   * Returns the path for the specified cache bin.
   *
   * @param string $bin
   *   The cache bin for which to return the path.
   *
   * @return string
   *   The path or URI to the folder where the cache files for the given bin
   *   will be stored.
   *
   * @throws \Exception
   *   Thrown when no path has been configured.
   */
  protected function getPathForBin($bin) {
    $settings = $this->settings->get('filecache');
    // Look for a cache bin specific setting.
    if (isset($settings['directory']['bins'][$bin])) {
      $path = rtrim($settings['directory']['bins'][$bin], '/') . '/';
    }
    // Fall back to the default path.
    elseif (isset($settings['directory']['default'])) {
      $path = rtrim($settings['directory']['default'], '/') . '/' . $bin . '/';
    }
    else {
      throw new \Exception('No path has been configured for the file system cache backend.');
    }
    return $path;
  }

  /**
   * Returns the cache strategy for the specified cache bin.
   *
   * @param string $bin
   *   The cache bin for which to return the cache strategy.
   *
   * @return string
   *   The cache strategy, either `FileSystemBackend::DEFAULT` or
   *   `FileSystemBackend::PERSIST`.
   */
  protected function getCacheStrategyForBin($bin) {
    $settings = $this->settings->get('filecache');
    // Look for a cache bin specific setting.
    if (isset($settings['strategy']['bins'][$bin])) {
      $strategy = $settings['strategy']['bins'][$bin];
    }
    // Check if a default strategy is defined.
    elseif (isset($settings['strategy']['default'])) {
      $strategy = $settings['strategy']['default'];
    }
    else {
      $strategy = FileSystemBackend::STANDARD;
    }
    return $strategy;
  }

}
