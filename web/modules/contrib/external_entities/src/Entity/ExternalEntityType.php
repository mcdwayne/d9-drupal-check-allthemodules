<?php

namespace Drupal\external_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\external_entities\ExternalEntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the external_entity_type entity.
 *
 * @ConfigEntityType(
 *   id = "external_entity_type",
 *   label = @Translation("External entity type"),
 *   handlers = {
 *     "list_builder" = "Drupal\external_entities\ExternalEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\external_entities\Form\ExternalEntityTypeForm",
 *       "edit" = "Drupal\external_entities\Form\ExternalEntityTypeForm",
 *       "delete" = "Drupal\external_entities\Form\ExternalEntityTypeDeleteForm",
 *     }
 *   },
 *   config_prefix = "external_entity_type",
 *   admin_permission = "administer external entity types",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/external-entity-types/{external_entity_type}",
 *     "delete-form" = "/admin/structure/external-entity-types/{external_entity_type}/delete",
 *   }
 * )
 */
class ExternalEntityType extends ConfigEntityBase implements ExternalEntityTypeInterface {

  /**
   * Indicates that entities of this external entity type should not be cached.
   */
  const CACHE_DISABLED = 0;

  /**
   * The external entity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the external entity type.
   *
   * @var string
   */
  protected $label;

  /**
   * The plural human-readable name of the external entity type.
   *
   * @var string
   */
  protected $label_plural;

  /**
   * The external entity type description.
   *
   * @var string
   */
  protected $description;

  /**
   * Whether or not entity types of this external entity type are read only.
   *
   * @var bool
   */
  protected $read_only;

  /**
   * The field mappings for this external entity type.
   *
   * @var array
   */
  protected $field_mappings = [];

  /**
   * The ID of the storage client plugin.
   *
   * @var string
   */
  protected $storage_client_id;

  /**
   * The storage client plugin configuration.
   *
   * @var array
   */
  protected $storage_client_config = [];

  /**
   * The storage client plugin instance.
   *
   * @var \Drupal\external_entities\StorageClient\ExternalEntityStorageClientInterface
   */
  protected $storageClientPlugin;

  /**
   * Max age entities of this external entity type may be persistently cached.
   *
   * @var int
   */
  protected $persistent_cache_max_age = self::CACHE_DISABLED;

  /**
   * The annotations entity type id.
   *
   * @var string
   */
  protected $annotation_entity_type_id;

  /**
   * The annotations bundle id.
   *
   * @var string
   */
  protected $annotation_bundle_id;

  /**
   * The field this external entity is referenced from by the annotation entity.
   *
   * @var string
   */
  protected $annotation_field_name;

  /**
   * Local cache for the annotation field.
   *
   * @var array
   *
   * @see ExternalEntityType::getAnnotationField()
   */
  protected $annotation_field;

  /**
   * Indicates if the external entity inherits the annotation entity fields.
   *
   * @var bool
   */
  protected $inherits_annotation_fields = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluralLabel() {
    return $this->label_plural;
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
  public function isReadOnly() {
    return $this->read_only;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMappings() {
    return $this->field_mappings;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMapping($field_name, $property_name = NULL) {
    if (!empty($this->field_mappings[$field_name])) {
      if ($property_name && !empty($this->field_mappings[$field_name][$property_name])) {
        return $this->field_mappings[$field_name][$property_name];
      }
      elseif (!$property_name) {
        return $this->field_mappings[$field_name];
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidStorageClient() {
    $storage_client_plugin_definition = \Drupal::service('plugin.manager.external_entities.storage_client')->getDefinition($this->getStorageClientId(), FALSE);
    return !empty($storage_client_plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageClientId() {
    return $this->storage_client_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageClient() {
    if (!$this->storageClientPlugin) {
      $storage_client_plugin_manager = \Drupal::service('plugin.manager.external_entities.storage_client');
      $config = $this->getStorageClientConfig();
      $config['_external_entity_type'] = $this;
      if (!($this->storageClientPlugin = $storage_client_plugin_manager->createInstance($this->getStorageClientId(), $config))) {
        $storage_client_id = $this->getStorageClientId();
        $label = $this->label();
        throw new \Exception("The storage client with ID '$storage_client_id' could not be retrieved for server '$label'.");
      }
    }
    return $this->storageClientPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageClientConfig() {
    return $this->storage_client_config ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function setStorageClientConfig(array $storage_client_config) {
    $this->storage_client_config = $storage_client_config;
    $this->getStorageClient()->setConfiguration($storage_client_config);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheMaxAge() {
    return $this->persistent_cache_max_age;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Clear the entity type definitions cache so changes flow through to the
    // related entity types.
    $this->entityTypeManager()->clearCachedDefinitions();

    // Clear the router cache to prevent RouteNotFoundException errors caused
    // by the Field UI module.
    \Drupal::service('router.builder')->rebuild();

    // Rebuild local actions so that the 'Add field' action on the 'Manage
    // fields' tab appears.
    \Drupal::service('plugin.manager.menu.local_action')->clearCachedDefinitions();

    // Clear the static and persistent cache.
    $storage->resetCache();
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivedEntityTypeId() {
    return $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivedEntityType() {
    return $this->entityTypeManager()->getDefinition($this->getDerivedEntityTypeId(), FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isAnnotatable() {
    return $this->getAnnotationEntityTypeId()
      && $this->getAnnotationBundleId()
      && $this->getAnnotationFieldName();
  }

  /**
   * {@inheritdoc}
   */
  public function getAnnotationEntityTypeId() {
    return $this->annotation_entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnnotationBundleId() {
    return $this->annotation_bundle_id ?: $this->getAnnotationEntityTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getAnnotationFieldName() {
    return $this->annotation_field_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnnotationField() {
    if (!isset($this->annotation_field) && $this->isAnnotatable()) {
      $field_definitions = $this->entityManager()->getFieldDefinitions($this->getAnnotationEntityTypeId(), $this->getAnnotationBundleId());
      $annotation_field_name = $this->getAnnotationFieldName();
      if (!empty($field_definitions[$annotation_field_name])) {
        $this->annotation_field = $field_definitions[$annotation_field_name];
      }
    }

    return $this->annotation_field;
  }

  /**
   * {@inheritdoc}
   */
  public function inheritsAnnotationFields() {
    return (bool) $this->inherits_annotation_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getBasePath() {
    return str_replace('_', '-', strtolower($this->id));
  }

}
