<?php

namespace Drupal\search_api_saved_searches\Entity;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\search_api\Utility\QueryHelperInterface;
use Drupal\search_api_saved_searches\Notification\NotificationPluginInterface;
use Drupal\search_api_saved_searches\SavedSearchesException;
use Drupal\search_api_saved_searches\SavedSearchTypeInterface;

/**
 * Provides an entity type for configuring how searches can be saved.
 *
 * @ConfigEntityType(
 *   id = "search_api_saved_search_type",
 *   label = @Translation("Saved search type"),
 *   label_collection = @Translation("Saved search type"),
 *   label_singular = @Translation("saved search type"),
 *   label_plural = @Translation("saved search types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count saved search type",
 *     plural = "@count saved search types",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\search_api_saved_searches\Entity\SavedSearchTypeStorage",
 *     "list_builder" = "Drupal\search_api_saved_searches\SavedSearchTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\search_api_saved_searches\Form\SavedSearchTypeForm",
 *       "edit" = "Drupal\search_api_saved_searches\Form\SavedSearchTypeForm",
 *       "delete" = "Drupal\search_api_saved_searches\Form\SavedSearchTypeDeleteConfirmForm",
 *     },
 *   },
 *   admin_permission = "administer search_api_saved_searches",
 *   config_prefix = "type",
 *   bundle_of = "search_api_saved_search",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "notification_settings",
 *     "options",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/search-api-saved-searches/type/{search_api_saved_search_type}/edit",
 *     "add-form" = "/admin/config/search/search-api-saved-searches/add-type",
 *     "edit-form" = "/admin/config/search/search-api-saved-searches/type/{search_api_saved_search_type}/edit",
 *     "delete-form" = "/admin/config/search/search-api-saved-searches/type/{search_api_saved_search_type}/delete",
 *     "collection" = "/admin/config/search/search-api-saved-searches",
 *   },
 * )
 */
class SavedSearchType extends ConfigEntityBundleBase implements SavedSearchTypeInterface {

  /**
   * The type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The type's (admin) description.
   *
   * @var string
   */
  protected $description;

  /**
   * The settings of the notification plugins selected for this index.
   *
   * The array has the following structure:
   *
   * @code
   * [
   *   'NOTIFICATION_PLUGIN_ID' => [
   *     // Settings …
   *   ],
   *   …
   * ]
   * @endcode
   *
   * @var array
   */
  protected $notification_settings = [];

  /**
   * The settings for this type.
   *
   * @var array
   */
  protected $options = [];

  /**
   * The instantiated notification plugins.
   *
   * In the ::preSave method we're saving the contents of these back into the
   * $notification_settings array. When adding, removing or changing
   * configuration we should therefore always manipulate this property instead
   * of the stored one.
   *
   * @var \Drupal\search_api_saved_searches\Notification\NotificationPluginInterface[]|null
   *
   * @see getNotificationPlugins()
   */
  protected $notificationPluginInstances;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If we are in the process of syncing, we shouldn't change any entity
    // properties (or other configuration).
    if ($this->isSyncing()) {
      return;
    }

    // @todo Do we need to check config overrides here?

    // Write the notification plugin settings to the persistent
    // $notification_settings property.
    $this->writeChangesToSettings();
  }

  /**
   * Prepares for changes to this saved search type to be persisted.
   *
   * To this end, the settings for all loaded notification plugin objects are
   * written back to the $notification_settings property.
   *
   * @return $this
   */
  protected function writeChangesToSettings() {
    // We only need to re-write the $notification_settings property if the
    // plugins were loaded.
    if ($this->notificationPluginInstances !== NULL) {
      $this->notification_settings = [];
      foreach ($this->notificationPluginInstances as $plugin_id => $plugin) {
        $this->notification_settings[$plugin_id] = $plugin->getConfiguration();
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update && !$this->isSyncing()) {
      $this->createFormDisplay();
    }

    // If notification plugins changed, we might have new field definitions (or
    // removed old ones).
    $original = $update ? $this->original : static::create(['id' => $this->id()]);
    $this->adaptFieldStorageDefinitions($original, $this);

    // @todo When changing the "date_field" for one or more indexes from/to the
    //   "Determine by result ID" option, we need to prime/delete the results
    //   data for all affected saved searches. (Needs index ID in searches.)
  }

  /**
   * Creates a "create" form display for a new saved search bundle.
   */
  protected function createFormDisplay() {
    try {
      $values = [
        'status' => TRUE,
        'id' => "search_api_saved_search.{$this->id()}.create",
        'targetEntityType' => 'search_api_saved_search',
        'bundle' => $this->id(),
        'mode' => 'create',
        'content' => [
          'label' => [
            'type' => 'string_textfield',
            'weight' => 0,
            'region' => 'content',
            'settings' => [
              'size' => 60,
              'placeholder' => '',
            ],
            'third_party_settings' => [],
          ],
          'notify_interval' => [
            'type' => 'options_select',
            'weight' => 1,
            'region' => 'content',
            'settings' => [],
            'third_party_settings' => [],
          ],
        ],
        'hidden' => [
          'status' => TRUE,
          'created' => TRUE,
          'langcode' => TRUE,
          'last_executed' => TRUE,
          'next_execution' => TRUE,
          'uid' => TRUE,
        ],
      ];
      foreach ($this->getNotificationPlugins() as $plugin) {
        $values['content'] += $plugin->getDefaultFieldFormDisplay();
      }
      EntityFormDisplay::create($values)->save();
    }
    catch (EntityStorageException $e) {
      $vars = ['%label' => $this->label()];
      watchdog_exception('search_api_saved_searches', $e, '%type while trying to configure the "Create" form display for the new saved search type %label: @message in %function (line %line of %file).', $vars);
    }
  }

  /**
   * Adapts field storage definitions to a changes in a type.
   *
   * @param \Drupal\search_api_saved_searches\SavedSearchTypeInterface $old
   *   The old version of the search type.
   * @param \Drupal\search_api_saved_searches\SavedSearchTypeInterface $new
   *   The new version of the search type.
   */
  protected static function adaptFieldStorageDefinitions(SavedSearchTypeInterface $old, SavedSearchTypeInterface $new) {
    if ($new->get('notification_settings') == $old->get('notification_settings')) {
      return;
    }

    // Clear the cache.
    \Drupal::getContainer()->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    // Determine changes in the fields defined for this type/bundle.
    $old_fields = $old->getNotificationPluginFieldDefinitions();
    $new_fields = $new->getNotificationPluginFieldDefinitions();

    // Collect all fields that exist for the entity type regardless of this
    // type/bundle (because they are (also) defined by other bundles).
    $fields_from_other_types = [];
    /** @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface $type */
    foreach (static::loadMultiple() as $type) {
      if ($type->id() != $old->id()) {
        $fields_from_other_types += $type->getNotificationPluginFieldDefinitions();
      }
    }

    // Compute the effective changes in field storage definitions.
    $created = array_diff_key($new_fields, $old_fields);
    $created = array_diff_key($created, $fields_from_other_types);
    $deleted = array_diff_key($old_fields, $new_fields);
    $deleted = array_diff_key($deleted, $fields_from_other_types);

    // Notify the field storage definition listener of all changes.
    $listener = \Drupal::getContainer()
      ->get('field_storage_definition.listener');
    foreach ($created as $field) {
      $listener->onFieldStorageDefinitionCreate($field);
    }
    foreach ($deleted as $field) {
      $listener->onFieldStorageDefinitionDelete($field);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Remove any searches for the deleted types. Normally, deleting a type with
    // existing searches should not be possible – but it's only forbidden via
    // the UI, not via an access handler, so w can't rely on that.
    // NB: $entities is not documented to be keyed by entity ID, but since Core
    // relies on it (see \Drupal\comment\Entity\Comment::postDelete()), we
    // should be able to do the same.
    $storage = \Drupal::entityTypeManager()
      ->getStorage('search_api_saved_search');
    $search_ids = $storage->getQuery()
      ->condition('type', array_keys($entities), 'IN')
      ->accessCheck(FALSE)
      ->execute();
    if ($search_ids) {
      $storage->delete($storage->loadMultiple($search_ids));
    }

    /** @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface $type */
    foreach ($entities as $type) {
      $new = static::create(['id' => $type->id()]);
      static::adaptFieldStorageDefinitions($type, $new);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotificationPlugins() {
    if ($this->notificationPluginInstances === NULL) {
      $this->notificationPluginInstances = \Drupal::getContainer()
        ->get('plugin.manager.search_api_saved_searches.notification')
        ->createPlugins($this, array_keys($this->notification_settings));
    }

    return $this->notificationPluginInstances;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotificationPluginIds() {
    return array_keys($this->getNotificationPlugins());
  }

  /**
   * {@inheritdoc}
   */
  public function isValidNotificationPlugin($notification_plugin_id) {
    $notification_plugins = $this->getNotificationPlugins();
    return !empty($notification_plugins[$notification_plugin_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getNotificationPlugin($notification_plugin_id) {
    $notification_plugins = $this->getNotificationPlugins();

    if (empty($notification_plugins[$notification_plugin_id])) {
      $index_label = $this->label();
      throw new SavedSearchesException("The datasource with ID '$notification_plugin_id' could not be retrieved for index '$index_label'.");
    }

    return $notification_plugins[$notification_plugin_id];
  }

  /**
   * {@inheritdoc}
   */
  public function addNotificationPlugin(NotificationPluginInterface $notification_plugin) {
    // Make sure the notificationPluginInstances are loaded before trying to add
    // a plugin to them.
    if ($this->notificationPluginInstances === NULL) {
      $this->getNotificationPlugins();
    }
    $this->notificationPluginInstances[$notification_plugin->getPluginId()] = $notification_plugin;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeNotificationPlugin($notification_plugin_id) {
    // Make sure the notificationPluginInstances are loaded before trying to
    // remove a plugin from them.
    if ($this->notificationPluginInstances === NULL) {
      $this->getNotificationPlugins();
    }
    unset($this->notificationPluginInstances[$notification_plugin_id]);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setNotificationPlugins(array $notification_plugins = NULL) {
    $this->notificationPluginInstances = $notification_plugins;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotificationPluginFieldDefinitions() {
    $fields = [];

    // Collect field definitions from our plugins.
    foreach ($this->getNotificationPlugins() as $plugin_id => $plugin) {
      $plugin_fields = $plugin->getFieldDefinitions();

      // Determine the plugin's provider.
      $definition = $plugin->getPluginDefinition();
      $provider = NULL;
      if ($definition instanceof PluginDefinitionInterface) {
        $provider = $definition->getProvider();
      }
      elseif (is_array($definition)) {
        $provider = $definition['provider'];
      }

      // Set some common settings on the field definitions.
      foreach ($plugin_fields as $field_name => $field) {
        $field->setName($field_name);
        $field->setTargetEntityTypeId('search_api_saved_search');
        $field->setProvider($provider);
        $field->setSetting('notification_plugin', $plugin_id);
      }

      $fields += $plugin_fields;
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($key, $default = NULL) {
    // @todo Some of the options (mail texts) need to be translatable. Is this
    //   the place to implement that (partly)?
    $keys = explode('.', $key);
    $value = NestedArray::getValue($this->options, $keys, $exists);
    return $exists ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveQuery(QueryHelperInterface $query_helper = NULL) {
    if (!$query_helper) {
      $query_helper = \Drupal::service('search_api.query_helper');
    }
    foreach ($query_helper->getAllResults() as $result) {
      // Only match queries with attached search display.
      $query = $result->getQuery();
      $display = $query->getDisplayPlugin();
      if (!$display) {
        continue;
      }
      // Check whether the display matches the ones selected in the options.
      // @todo Replace with \Drupal\search_api\Utility\Utility::matches() once
      //   we can use it (Search API 1.8 dependency).
      $display_id = $display->getPluginId();
      $selected = $this->getOption('displays.selected', []);
      $default = $this->getOption('displays.default', TRUE);
      if (in_array($display_id, $selected) != $default) {
        return $query;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = $this->getDependencyData();
    // Keep only "enforced" dependencies, then add those computed by
    // getDependencyData().
    $this->dependencies = array_intersect_key($this->dependencies, ['enforced' => TRUE]);
    $this->dependencies += array_map('array_keys', $dependencies);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    $all_plugins = $this->getNotificationPlugins();
    $dependency_data = $this->getDependencyData();
    // Make sure our dependency data has the exact same keys as $dependencies,
    // to simplify the subsequent code.
    $dependencies = array_filter($dependencies);
    $dependency_data = array_intersect_key($dependency_data, $dependencies);
    $dependency_data += array_fill_keys(array_keys($dependencies), []);
    $call_on_removal = [];

    foreach ($dependencies as $dependency_type => $dependency_objects) {
      // Annoyingly, modules and theme dependencies come not keyed by dependency
      // name here, while entities do. Flip the array for modules and themes to
      // make the code simpler.
      if (in_array($dependency_type, ['module', 'theme'])) {
        $dependency_objects = array_flip($dependency_objects);
      }
      $dependency_data[$dependency_type] = array_intersect_key($dependency_data[$dependency_type], $dependency_objects);
      foreach ($dependency_data[$dependency_type] as $name => $dependency_sources) {
        // We first remove all the "hard" dependencies.
        if (!empty($dependency_sources['always'])) {
          // This will definitely lead to a change (or to us returning FALSE
          // directly).
          $changed = TRUE;

          foreach ($dependency_sources['always'] as $plugin_type => $plugins) {
            // We can hardly remove the entity itself, so just give up.
            if ($plugin_type === 'entity') {
              return FALSE;
            }

            // Otherwise, the dependency has to come from one or more
            // notification plugins. So, remove them.
            $all_plugins = array_diff_key($all_plugins, $plugins);
          }
        }

        // Then, collect all the optional ones.
        if (!empty($dependency_sources['optional'])) {
          // However this plays out, it will lead to a change.
          $changed = TRUE;

          foreach ($dependency_sources['optional'] as $plugin_type => $plugins) {
            // Type entities currently have no soft dependencies, so this has to
            // be a plugin dependency. We want to call onDependencyRemoval() on
            // that plugin.

            // Only include those plugins that have not already been removed.
            $plugins = array_intersect_key($plugins, $all_plugins);

            foreach ($plugins as $plugin_id => $plugin) {
              $call_on_removal[$plugin_type][$plugin_id][$dependency_type][$name] = $dependency_objects[$name];
            }
          }
        }
      }
    }

    // Now for all plugins with optional dependencies (stored in
    // $call_on_removal, mapped to their removed dependencies) call their
    // onDependencyRemoval() methods.
    $updated_config = [];
    foreach ($call_on_removal as $plugin_type => $plugins) {
      foreach ($plugins as $plugin_id => $plugin_dependencies) {
        $removal_successful = $all_plugins[$plugin_id]->onDependencyRemoval($plugin_dependencies);
        // If the plugin was successfully changed to remove the dependency,
        // remember the new configuration to later set it. Otherwise, remove the
        // plugin from the index so the dependency still gets removed.
        if ($removal_successful) {
          $updated_config[$plugin_type][$plugin_id] = $all_plugins[$plugin_id]->getConfiguration();
        }
        else {
          unset($all_plugins[$plugin_id]);
        }
      }
    }

    // Finally, apply the changes by removing all plugins that have been unset
    // from $all_plugins.
    $this->notificationPluginInstances = array_intersect_key($this->notificationPluginInstances, $all_plugins);

    return $changed;
  }

  /**
   * Retrieves data about this type's dependencies.
   *
   * The return value is structured as follows:
   *
   * @code
   * [
   *   'config' => [
   *     'CONFIG_DEPENDENCY_KEY' => [
   *       'always' => [
   *         'entity' => [
   *           'TYPE_ID' => $type,
   *         ],
   *         'notification' => [
   *           'NOTIFICATION_ID' => $notification_plugin,
   *         ],
   *       ],
   *       'optional' => [
   *         'notification' => [
   *           'NOTIFICATION_ID' => $notification_plugin,
   *         ],
   *       ],
   *     ],
   *   ],
   * ]
   * @endcode
   *
   * Enforced dependencies are not included in this method's return value.
   *
   * @return object[][][][][]
   *   An associative array containing the type's dependencies. The array is
   *   first keyed by the config dependency type ("module", "config", etc.) and
   *   then by the names of the config dependencies of that type which the
   *   entity has. The values are associative arrays with up to two keys,
   *   "always" and "optional", specifying whether the dependency is a hard one
   *   by the plugin (or entity) in question or potentially depending on the
   *   configuration. The values on this level are arrays with keys "entity"
   *   and/or "notification" and values arrays of IDs mapped to their
   *   entities/plugins.
   */
  protected function getDependencyData() {
    $dependency_data = [];

    // Since calculateDependencies() will work directly on the $dependencies
    // property, we first save its original state and then restore it
    // afterwards.
    $original_dependencies = $this->dependencies;
    parent::calculateDependencies();
    unset($this->dependencies['enforced']);
    foreach ($this->dependencies as $dependency_type => $list) {
      foreach ($list as $name) {
        $dependency_data[$dependency_type][$name]['always']['entity'][$this->id] = $this;
      }
    }
    $this->dependencies = $original_dependencies;

    // In theory, we also depend on all existing indexes (since we store their
    // IDs in $this->options['date_field']) and all search displays (in
    // $this->options['displays']). However, since having unknown plugin/entity
    // IDs there doesn't really cause any problems, we don't include them as
    // optional dependencies here.

    $plugins = $this->getNotificationPlugins();
    foreach ($plugins as $plugin_id => $plugin) {
      // Largely copied from
      // \Drupal\Core\Plugin\PluginDependencyTrait::calculatePluginDependencies().
      $definition = $plugin->getPluginDefinition();

      // First, always depend on the module providing the plugin.
      $dependency_data['module'][$definition['provider']]['always']['notification'][$plugin_id] = $plugin;

      // Plugins can declare additional dependencies in their definition.
      if (isset($definition['config_dependencies'])) {
        foreach ($definition['config_dependencies'] as $dependency_type => $list) {
          foreach ($list as $name) {
            $dependency_data[$dependency_type][$name]['always']['notification'][$plugin_id] = $plugin;
          }
        }
      }

      // Then, add the dynamically-calculated dependencies of the plugin.
      foreach ($plugin->calculateDependencies() as $dependency_type => $list) {
        foreach ($list as $name) {
          $dependency_data[$dependency_type][$name]['optional']['notification'][$plugin_id] = $plugin;
        }
      }
    }

    return $dependency_data;
  }

  /**
   * Implements the magic __sleep() method.
   *
   * Prevents the instantiated plugins from being serialized.
   */
  public function __sleep() {
    // First, write any plugin changes to the persistent properties so they
    // won't be discarded.
    $this->writeChangesToSettings();

    // Then, return a list of all properties that don't contain objects.
    $properties = get_object_vars($this);
    unset($properties['notificationPluginInstances']);
    return array_keys($properties);
  }

}
