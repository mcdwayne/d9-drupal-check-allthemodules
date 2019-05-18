<?php

namespace Drupal\patchinfo;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages patch source plugins.
 */
class PatchInfoSourceManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PatchInfo/Source', $namespaces, $module_handler, 'Drupal\patchinfo\PatchInfoSourceInterface', 'Drupal\patchinfo\Annotation\PatchInfoSource');
    $this->alterInfo('patchinfo_source');
    $this->setCacheBackend($cache_backend, 'patchinfo_source');
  }

}
