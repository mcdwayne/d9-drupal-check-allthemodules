<?php

namespace Drupal\presshub;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Presshub plugin manager.
 */
class PresshubManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Presshub', $namespaces, $module_handler, 'Drupal\presshub\PresshubInterface', 'Drupal\presshub\Annotation\Presshub');
    $this->alterInfo('presshub_info');
    $this->setCacheBackend($cache_backend, 'presshub');
  }

}
