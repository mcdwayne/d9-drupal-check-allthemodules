<?php

namespace Drupal\search_api_saved_searches\Notification;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\search_api_saved_searches\SavedSearchTypeInterface;

/**
 * Provides an interface for the notification plugin manager.
 */
interface NotificationPluginManagerInterface extends PluginManagerInterface {

  /**
   * Creates a notification plugin for the given saved search type.
   *
   * @param \Drupal\search_api_saved_searches\SavedSearchTypeInterface $type
   *   The saved search type.
   * @param string $plugin_id
   *   The ID of the notification plugin to create.
   * @param array $configuration
   *   (optional) The configuration to set for the plugin.
   *
   * @return \Drupal\search_api_saved_searches\Notification\NotificationPluginInterface
   *   The created notification plugin.
   *
   * @throws \Drupal\search_api_saved_searches\SavedSearchesException
   *   Thrown if an unknown plugin ID is given.
   */
  public function createPlugin(SavedSearchTypeInterface $type, $plugin_id, array $configuration = []);

  /**
   * Creates multiple notification plugins for the given saved search type.
   *
   * @param \Drupal\search_api_saved_searches\SavedSearchTypeInterface $type
   *   The saved search type.
   * @param string[]|null $plugin_ids
   *   (optional) The IDs of the plugins to create, or NULL to create instances
   *   for all known notification plugins.
   * @param array $configurations
   *   (optional) The configurations to set for the plugins, keyed by plugin ID.
   *   Missing configurations are either taken from the saved search type's
   *   stored settings, if they are present there, or default to an empty array.
   *
   * @return \Drupal\search_api_saved_searches\Notification\NotificationPluginInterface[]
   *   The created notification plugins.
   *
   * @throws \Drupal\search_api_saved_searches\SavedSearchesException
   *   Thrown if an unknown plugin ID is given.
   */
  public function createPlugins(SavedSearchTypeInterface $type, array $plugin_ids = NULL, array $configurations = []);

}
