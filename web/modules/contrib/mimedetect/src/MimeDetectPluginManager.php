<?php

namespace Drupal\mimedetect;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\mimedetect\Annotation\MimeDetector;

/**
 * A plugin manager for mime detector plugins.
 */
class MimeDetectPluginManager extends DefaultPluginManager {

  /**
   * Creates a MimeDetectPluginManager object.
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
    parent::__construct('Plugin/MimeDetector', $namespaces, $module_handler, MimeDetectorInterface::class, MimeDetector::class);
    $this->alterInfo('mime_detector_info');
    $this->setCacheBackend($cache_backend, 'mime_detector_info');
  }

}
