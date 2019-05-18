<?php

namespace Drupal\file_downloader;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\file\FileInterface;
use Drupal\file_downloader\Entity\DownloadOptionConfigInterface;

/**
 * Provides an Download option plugin manager.
 *
 * @see plugin_api
 */
class DownloadOptionPluginManager extends DefaultPluginManager {

  /**
   * An array of download options.
   *
   * @var array
   */
  private $downloadOptionsPluginOptions;

  /**
   * Constructs a DownloadOptionPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/DownloadOption',
      $namespaces,
      $module_handler,
      'Drupal\file_downloader\DownloadOptionPluginInterface',
      'Drupal\file_downloader\Annotation\DownloadOption'
    );
    $this->alterInfo('download_option');
    $this->setCacheBackend($cache_backend, 'download_options');
  }

  /**
   * Returns an array of widget type options for a field type.
   *
   * @return array
   *
   */
  public function getOptions() {
    if (isset($this->downloadOptionsPluginOptions)) {
        return $this->downloadOptionsPluginOptions;
    }

    $options = array();
    $download_options = $this->getDefinitions();
    foreach ($download_options as $name => $download_option_plugin) {
      $options[$name] = $download_option_plugin['label'];
    }

    $this->downloadOptionsPluginOptions = $options;

    return $this->downloadOptionsPluginOptions;
  }

}
