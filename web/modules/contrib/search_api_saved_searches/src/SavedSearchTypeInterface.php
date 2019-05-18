<?php

namespace Drupal\search_api_saved_searches;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\search_api\Utility\QueryHelperInterface;
use Drupal\search_api_saved_searches\Notification\NotificationPluginInterface;

/**
 * Provides an interface for saved search types.
 */
interface SavedSearchTypeInterface extends ConfigEntityInterface {

  /**
   * Retrieves the type's description.
   *
   * @return string
   *   The (admin) description of this saved search type.
   */
  public function getDescription();

  /**
   * Retrieves this saved search type's notification plugins.
   *
   * @return \Drupal\search_api_saved_searches\Notification\NotificationPluginInterface[]
   *   The notification plugins used by this saved search type, keyed by plugin
   *   ID.
   */
  public function getNotificationPlugins();

  /**
   * Retrieves the IDs of all notification plugins enabled for this type.
   *
   * @return string[]
   *   The IDs of the notification plugins used by this saved search type.
   */
  public function getNotificationPluginIds();

  /**
   * Determines whether the given notification plugin ID is valid for this type.
   *
   * The general contract of this method is that it should return TRUE if, and
   * only if, a call to getNotificationPlugin() with the same ID would not
   * result in an exception.
   *
   * @param string $notification_plugin_id
   *   A notification plugin ID.
   *
   * @return bool
   *   TRUE if the notification plugin with the given ID is enabled for this
   *   saved search type and can be loaded. FALSE otherwise.
   */
  public function isValidNotificationPlugin($notification_plugin_id);

  /**
   * Retrieves a specific notification plugin for this saved search type.
   *
   * @param string $notification_plugin_id
   *   The ID of the notification plugin to return.
   *
   * @return \Drupal\search_api_saved_searches\Notification\NotificationPluginInterface
   *   The notification plugin with the given ID.
   *
   * @throws \Drupal\search_api_saved_searches\SavedSearchesException
   *   Thrown if the specified notification plugin isn't enabled for this saved
   *   search type, or couldn't be loaded.
   */
  public function getNotificationPlugin($notification_plugin_id);

  /**
   * Adds a notification plugin to this saved search type.
   *
   * An existing notification plugin with the same ID will be replaced.
   *
   * @param \Drupal\search_api_saved_searches\Notification\NotificationPluginInterface $notification_plugin
   *   The notification plugin to be added.
   *
   * @return $this
   */
  public function addNotificationPlugin(NotificationPluginInterface $notification_plugin);

  /**
   * Removes a notification plugin from this saved search type.
   *
   * @param string $notification_plugin_id
   *   The ID of the notification plugin to remove.
   *
   * @return $this
   */
  public function removeNotificationPlugin($notification_plugin_id);

  /**
   * Sets this saved search type's notification plugins.
   *
   * @param \Drupal\search_api_saved_searches\Notification\NotificationPluginInterface[] $notification_plugins
   *   An array of notification plugins.
   *
   * @return $this
   */
  public function setNotificationPlugins(array $notification_plugins);

  /**
   * Retrieves all field definitions defined by notification plugins.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   All field definitions defined by notification plugins for this type,
   *   keyed by field name.
   */
  public function getNotificationPluginFieldDefinitions();

  /**
   * Retrieves the type options.
   *
   * @return array
   *   The options set for this type.
   */
  public function getOptions();

  /**
   * Retrieves a single, possibly nested, option.
   *
   * @param string $key
   *   The key of the option. Can contain periods (.) to access nested options.
   * @param mixed $default
   *   (optional) The value to return if the option wasn't set.
   *
   * @return mixed
   *   The value of the specified option if it exists, $default otherwise.
   */
  public function getOption($key, $default = NULL);

  /**
   * Retrieves an active search query that can be saved with this type.
   *
   * @param \Drupal\search_api\Utility\QueryHelperInterface|null $query_helper
   *   (optional) The query helper service to use. Otherwise, it will be
   *   retrieved from the container.
   *
   * @return \Drupal\search_api\Query\QueryInterface|null
   *   A search query that was executed in this page request and which can be
   *   saved with this saved search type. Or NULL if no such query could be
   *   found.
   */
  public function getActiveQuery(QueryHelperInterface $query_helper = NULL);

}
