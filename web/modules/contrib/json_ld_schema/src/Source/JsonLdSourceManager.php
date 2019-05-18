<?php

namespace Drupal\json_ld_schema\Source;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\json_ld_schema\Annotation\JsonLdSource;

/**
 * A plugin manager for JsonLdSchema plugins.
 */
class JsonLdSourceManager extends DefaultPluginManager implements JsonLdSourceManagerInterface {

  /**
   * JsonLdSourceManager constructor.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/JsonLdSource', $namespaces, $module_handler, JsonLdSourceInterface::class, JsonLdSource::class);
    $this->alterInfo('json_ld_source_info');
    $this->setCacheBackend($cache_backend, 'json_ld_source_plugins');
  }

}
