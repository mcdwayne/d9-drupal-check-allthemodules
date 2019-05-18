<?php

namespace Drupal\search_api_saved_searches\Notification;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Plugin\ConfigurablePluginInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api_saved_searches\SavedSearchInterface;
use Drupal\search_api_saved_searches\SavedSearchTypeInterface;

/**
 * Provides an interface for notification plugins.
 *
 * @see \Drupal\search_api_saved_searches\Annotation\SearchApiSavedSearchesNotification
 * @see \Drupal\search_api_saved_searches\Notification\NotificationPluginManager
 * @see \Drupal\search_api_saved_searches\Notification\NotificationPluginBase
 * @see plugin_api
 */
interface NotificationPluginInterface extends ConfigurablePluginInterface {

  /**
   * Retrieves the saved search type.
   *
   * @return \Drupal\search_api_saved_searches\SavedSearchTypeInterface
   *   The saved search type to which this plugin is attached.
   */
  public function getSavedSearchType();

  /**
   * Sets the saved search type.
   *
   * @param \Drupal\search_api_saved_searches\SavedSearchTypeInterface $type
   *   The new saved search type for this plugin.
   *
   * @return $this
   */
  public function setSavedSearchType(SavedSearchTypeInterface $type);

  /**
   * Retrieves the field definitions to add to saved searches for this plugin.
   *
   * The field definitions will be added to all bundles for which this
   * notification plugin is active.
   *
   * If an existing plugin's field definitions change in any way, it is the
   * providing module's responsibility to provide an update hook calling field
   * storage definition listener's CRUD methods as appropriate.
   *
   * @return \Drupal\search_api_saved_searches\BundleFieldDefinition[]
   *   An array of bundle field definitions, keyed by field name.
   */
  public function getFieldDefinitions();

  /**
   * Retrieves default form display settings for the plugin's custom fields.
   *
   * @return array
   *   An associative array of form display settings, keyed by field names
   *   defined by this plugin. Fields can easily be hidden by default by just
   *   omitting them from this array.
   */
  public function getDefaultFieldFormDisplay();

  /**
   * Checks access to an operation on a given entity field.
   *
   * This method will only be called for fields defined by this plugin and can
   * be used to implement custom access restrictions for those fields.
   *
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view" or "edit".
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   (optional) The field values for which to check access, or NULL if access
   *   is checked for the field definition, without any specific value
   *   available.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see \Drupal\search_api_saved_searches\Entity\SavedSearchAccessControlHandler::checkFieldAccess()
   */
  public function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL);

  /**
   * Notifies the search's owner of new results.
   *
   * @param \Drupal\search_api_saved_searches\SavedSearchInterface $search
   *   The saved search for which to report new results.
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   The new results.
   */
  public function notify(SavedSearchInterface $search, ResultSetInterface $results);

}
