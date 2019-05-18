<?php

namespace Drupal\crm_core_activity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\crm_core_activity\ActivityTypeInterface;

/**
 * CRM Activity Type Entity Class.
 *
 * @ConfigEntityType(
 *   id = "crm_core_activity_type",
 *   label = @Translation("CRM Core Activity type"),
 *   bundle_of = "crm_core_activity",
 *   config_prefix = "type",
 *   handlers = {
 *     "access" = "Drupal\crm_core_activity\ActivityTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\crm_core_activity\Form\ActivityTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "toggle" = "Drupal\crm_core_activity\Form\ActivityTypeToggleForm",
 *     },
 *     "list_builder" = "Drupal\crm_core_activity\ActivityTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer activity types",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name",
 *   },
 *   config_export = {
 *     "name",
 *     "type",
 *     "description",
 *     "activity_string",
 *     "plugin_id",
 *     "plugin_configuration",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/crm-core/activity-types/add",
 *     "edit-form" = "/admin/structure/crm-core/activity-types/{crm_core_activity_type}",
 *     "delete-form" = "/admin/structure/crm-core/activity-types/{crm_core_activity_type}/delete",
 *     "enable" = "/crm_core_activity.type_enable",
 *     "disable" = "/crm_core_activity.type_disable",
 *   }
 * )
 */
class ActivityType extends ConfigEntityBundleBase implements ActivityTypeInterface {

  /**
   * The machine-readable name of this type.
   *
   * @var string
   */
  public $type = '';

  /**
   * The human-readable name of this type.
   *
   * @var string
   */
  public $name = '';

  /**
   * A brief description of this type.
   *
   * @var string
   */
  public $description = '';

  /**
   * Text describing the relationship between the contact and this activity.
   *
   * @var string
   */
  public $activity_string;

  /**
   * Should new entities of this bundle have a new revision by default.
   *
   * @var bool
   */
  public $newRevision = TRUE;

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $plugin_id = 'generic';

  /**
   * The plugin configuration.
   *
   * @var array
   */
  public $plugin_configuration = [];

  /**
   * Type lazy plugin collection.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   *
   * @todo This does not scale.
   *
   * Deleting a activity type with thousands of activities records associated
   * will run into execution timeout.
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    $ids = array_map(function (EntityInterface $entity) {
      return $entity->id();
    }, $entities);

    // Delete all instances of the given type.
    $results = \Drupal::entityQuery('crm_core_activity')
      ->condition('type', $ids, 'IN')
      ->execute();

    if (!empty($results)) {
      $activities = Activity::loadMultiple($results);
      \Drupal::entityTypeManager()->getStorage('crm_core_activity')->delete($activities);
      // @todo Handle singular and plural.
      \Drupal::logger('crm_core_activity')->info('Delete !count activities due to deletion of activity type.', ['!count' => count($results)]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->newRevision;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->plugin_id = $plugin_id;
    $this->pluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginConfiguration(array $plugin_configuration) {
    $this->plugin_configuration = $plugin_configuration;
    $this->pluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'plugin_configuration' => $this->getPluginCollection(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.crm_core_activity.activity_type'), $this->plugin_id, $this->plugin_configuration);
    }
    return $this->pluginCollection;
  }

}
