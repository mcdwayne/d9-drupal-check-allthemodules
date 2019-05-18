<?php

namespace Drupal\ad_entity;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service class providing ad_entity plugin usage information.
 */
class AdEntityUsage {

  /**
   * The storage of Advertising entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The services commonly used by Advertising entities.
   *
   * @var \Drupal\ad_entity\AdEntityServices
   */
  protected $services;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * AdEntityUsage constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ad_entity\AdEntityServices $ad_entity_services
   *   The services commonly used by Advertising entities.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AdEntityServices $ad_entity_services, CacheBackendInterface $cache) {
    $this->entityStorage = $entity_type_manager->getStorage('ad_entity');
    $this->services = $ad_entity_services;
    $this->cache = $cache;
  }

  /**
   * Get all view plugin ids, which are being used by Advertising entities.
   *
   * @return array
   *   An array, keyed by plugin id. When the value is TRUE,
   *   the plugin is in use, FALSE otherwise.
   */
  public function getCurrentlyUsedAdViewPlugins() {
    return $this->getCurrentlyUsedPlugins('view_plugin_id', 'ad_entity.used_view_plugins', $this->services->getViewManager());
  }

  /**
   * Get all type plugin ids, which are being used by Advertising entities.
   *
   * @return array
   *   An array, keyed by plugin id. When the value is TRUE,
   *   the plugin is in use, FALSE otherwise.
   */
  public function getCurrentlyUsedAdTypePlugins() {
    return $this->getCurrentlyUsedPlugins('type_plugin_id', 'ad_entity.used_type_plugins', $this->services->getTypeManager());
  }

  /**
   * Generic method to get the currently used plugin ids.
   *
   * @param string $key
   *   The property key of the Advertising entities.
   * @param string $cid
   *   The cache key to use.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The corresponding plugin manager.
   *
   * @return array
   *   An array, keyed by plugin id. When the value is TRUE,
   *   the plugin is in use, FALSE otherwise.
   */
  protected function getCurrentlyUsedPlugins($key, $cid, PluginManagerInterface $manager) {
    $cache = $this->cache;
    if ($cached = $cache->get($cid)) {
      return $cached->data;
    }

    $used_plugins = [];
    if ($plugin_ids = $manager->getDefinitions()) {
      foreach (array_keys($plugin_ids) as $plugin_id) {
        $used_plugins[$plugin_id] = FALSE;
      }
    }

    // @todo Use an iterator or generator once
    // https://www.drupal.org/project/drupal/issues/2577417 is in.
    /** @var \Drupal\ad_entity\Entity\AdEntityInterface $ad_entity */
    foreach ($this->entityStorage->loadMultiple() as $ad_entity) {
      if ($plugin_id = $ad_entity->get($key)) {
        if (isset($used_plugins[$plugin_id])) {
          $used_plugins[$plugin_id] = TRUE;
        }
      }
    }

    $cache->set($cid, $used_plugins, Cache::PERMANENT, ['config:ad_entity_list']);

    return $used_plugins;
  }

}
