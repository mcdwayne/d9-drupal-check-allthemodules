<?php

namespace Drupal\search_api_saved_searches\Notification;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\search_api_saved_searches\SavedSearchesException;
use Drupal\search_api_saved_searches\SavedSearchTypeInterface;

/**
 * Manages notification plugins.
 *
 * @see \Drupal\search_api_saved_searches\Annotation\SearchApiSavedSearchesNotification
 * @see \Drupal\search_api_saved_searches\Notification\NotificationPluginInterface
 * @see \Drupal\search_api_saved_searches\Notification\NotificationPluginBase
 * @see plugin_api
 */
class NotificationPluginManager extends DefaultPluginManager implements NotificationPluginManagerInterface {

  /**
   * Constructs a NotificationPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/search_api_saved_searches/notification', $namespaces, $module_handler, 'Drupal\search_api_saved_searches\Notification\NotificationPluginInterface', 'Drupal\search_api_saved_searches\Annotation\SearchApiSavedSearchesNotification');

    $this->setCacheBackend($cache_backend, 'search_api_saved_searches_notification');
    $this->alterInfo('search_api_saved_searches_notification_info');
  }

  /**
   * {@inheritdoc}
   */
  public function createPlugin(SavedSearchTypeInterface $type, $plugin_id, array $configuration = NULL) {
    try {
      $configuration['#saved_search_type'] = $type;
      return $this->createInstance($plugin_id, $configuration);
    }
    catch (PluginException $e) {
      throw new SavedSearchesException("Unknown notification plugin with ID '$plugin_id'");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPlugins(SavedSearchTypeInterface $type, array $plugin_ids = NULL, array $configurations = []) {
    if ($plugin_ids === NULL) {
      $plugin_ids = array_keys($this->getDefinitions());
    }

    $plugins = [];
    $type_settings = $type->get('notification_settings');
    foreach ($plugin_ids as $plugin_id) {
      $configuration = [];
      if (isset($configurations[$plugin_id])) {
        $configuration = $configurations[$plugin_id];
      }
      elseif (isset($type_settings[$plugin_id])) {
        $configuration = $type_settings[$plugin_id];
      }
      $plugins[$plugin_id] = $this->createPlugin($type, $plugin_id, $configuration);
    }

    return $plugins;
  }

}
