<?php

/**
 * @file
 * Contains FieldComparatorPluginManager.php.
 */

namespace Drupal\changed_fields;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

class FieldComparatorPluginManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FieldComparator', $namespaces, $module_handler);
    $this->alterInfo('changed_fields_field_comparators_info');
    $this->setCacheBackend($cache_backend, 'changed_fields_plugins');
  }

}
