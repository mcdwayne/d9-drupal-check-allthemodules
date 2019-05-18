<?php

namespace Drupal\search_api_saved_searches\Notification;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Plugin\ConfigurablePluginBase;
use Drupal\search_api_saved_searches\SavedSearchTypeInterface;

/**
 * Defines a base class for notification plugins.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. These definition arrays may be altered through
 * hook_search_api_saved_searches_notification_info_alter(). The definition
 * includes the following keys:
 * - id: The unique, system-wide identifier of the notification plugin.
 * - label: The human-readable name of the notification plugin, translated.
 * - description: A human-readable description for the notification plugin,
 *   translated.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SearchApiSavedSearchesNotification(
 *   id = "my_notification",
 *   label = @Translation("My notification"),
 *   description = @Translation("This is my notification plugin."),
 * )
 * @endcode
 *
 * @see \Drupal\search_api_saved_searches\Annotation\SearchApiSavedSearchesNotification
 * @see \Drupal\search_api_saved_searches\Notification\DataTypePluginManager
 * @see \Drupal\search_api_saved_searches\Notification\NotificationPluginInterface
 * @see plugin_api
 */
abstract class NotificationPluginBase extends ConfigurablePluginBase implements NotificationPluginInterface {

  /**
   * The saved search type to which this plugin is attached.
   *
   * @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface
   */
  protected $savedSearchType;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    if (!empty($configuration['#saved_search_type']) && $configuration['#saved_search_type'] instanceof SavedSearchTypeInterface) {
      $this->setSavedSearchType($configuration['#saved_search_type']);
      unset($configuration['#saved_search_type']);
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getSavedSearchType() {
    return $this->savedSearchType;
  }

  /**
   * {@inheritdoc}
   */
  public function setSavedSearchType(SavedSearchTypeInterface $type) {
    $this->savedSearchType = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldFormDisplay() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    return AccessResult::allowed();
  }

}
