<?php

namespace Drupal\json_ld_schema\Entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\json_ld_schema\Annotation\JsonLdEntity;

/**
 * A plugin manager for JsonLdSchema plugins.
 */
class JsonLdEntityManager extends DefaultPluginManager {

  /**
   * JsonLdSourceManager constructor.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/JsonLdEntity', $namespaces, $module_handler, JsonLdEntityInterface::class, JsonLdEntity::class);
    $this->alterInfo('json_ld_entity_info');
    $this->setCacheBackend($cache_backend, 'json_ld_entity_plugins');
  }

}
