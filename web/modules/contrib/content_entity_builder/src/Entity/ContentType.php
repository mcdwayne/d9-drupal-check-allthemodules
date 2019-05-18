<?php

namespace Drupal\content_entity_builder\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\content_entity_builder\ContentTypeInterface;
use Drupal\content_entity_builder\BaseFieldConfigInterface;
use Drupal\content_entity_builder\BaseFieldConfigPluginCollection;

/**
 * Defines the Content Entity Type config entities.
 *
 * @ConfigEntityType(
 *   id = "content_type",
 *   label = @Translation("Content entity type"),
 *   handlers = {
 *     "list_builder" = "Drupal\content_entity_builder\ContentTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\content_entity_builder\Form\ContentTypeAddForm",
 *       "add" = "Drupal\content_entity_builder\Form\ContentTypeAddForm",
 *       "edit" = "Drupal\content_entity_builder\Form\ContentTypeEditForm",
 *       "delete" = "Drupal\content_entity_builder\Form\ContentTypeDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer content entity types",
 *   config_prefix = "content_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/content-types/manage/{content_type}",
 *     "add-form" = "/admin/structure/content-types/add",
 *     "edit-form" = "/admin/structure/content-types/manage/{content_type}",
 *     "delete-form" = "/admin/structure/content-types/manage/{content_type}/delete",
 *     "collection" = "/admin/structure/content-types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "keys",
 *     "paths",
 *     "applied",
 *     "basefields"
 *   }
 * )
 *
 * @ingroup content_entity_builder
 */
class ContentType extends ConfigEntityBase implements ContentTypeInterface, EntityWithPluginCollectionInterface {


  /**
   * The id of the content entity type.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the content entity type.
   *
   * @var string
   */
  protected $label;

  /**
   * Indicate whether apply update for this content entity type.
   *
   * @var bool
   */
  protected $applied = FALSE;

  /**
   * The array of base_fields, could not use base_fields.
   *
   * @var array
   */
  public $basefields = [];

  /**
   * Holds the collection of base_fields.
   *
   * @var \Drupal\content_entity_builder\BaseFieldConfigPluginCollection
   */
  protected $baseFieldsCollection;

  /**
   * The array of entity keys for this content entity type.
   *
   * @var array
   */
  public $keys = [];

  /**
   * The array of entity paths for this content entity type.
   *
   * @var array
   */
  public $paths = [];

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplied() {
    return $this->applied;
  }

  /**
   * {@inheritdoc}
   */
  public function setApplied($applied) {
    $this->applied = $applied;
    return $applied;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityKeys() {
    return $this->keys;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityKeys(array $entity_keys) {
    $this->keys = $entity_keys;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityKey($key) {
    $keys = $this->getEntityKeys();
    $entity_key = isset($keys[$key]) ? $keys[$key] : '';
    return $entity_key;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityPaths() {
    return $this->paths;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityPaths(array $entity_paths) {
    $this->paths = $entity_paths;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityPath($path) {
    $paths = $this->setEntityPaths();
    $entity_path = isset($paths[$path]) ? $paths[$path] : '';
    return $entity_path;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteBaseField(BaseFieldConfigInterface $base_field) {
    $this->getBaseFields()->removeInstanceId($base_field->getFieldName());
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseField($base_field) {
    return isset($this->basefields[$base_field]) ? $this->getBaseFields()->get($base_field) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFields() {
    if (!$this->baseFieldsCollection) {
      $this->baseFieldsCollection = new BaseFieldConfigPluginCollection($this->getBaseFieldConfigPluginManager(), $this->basefields);
      $this->baseFieldsCollection->sort();
    }
    return $this->baseFieldsCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData() {
    $is_applied = $this->isApplied();
    $has_data = FALSE;
    if (!empty($is_applied)) {
      $has_data = $this->entityManager()->getStorage($this->id())->hasData();
    }
    return ($is_applied && $has_data);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['basefields' => $this->getBaseFields()];
  }

  /**
   * {@inheritdoc}
   */
  public function addBaseField(array $configuration) {
    $this->getBaseFields()->addInstanceId($configuration['field_name'], $configuration);
    return $configuration['field_name'];
  }

  /**
   * Returns the base field config plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The base field config plugin manager.
   */
  protected function getBaseFieldConfigPluginManager() {
    return \Drupal::service('plugin.manager.content_entity_builder.base_field_config');
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Delete content entity type before delete config entity.
    if (!empty($this->isApplied())) {
      $entity_update_manager = \Drupal::entityDefinitionUpdateManager();
      $entity_type = $entity_update_manager->getEntityType($this->id());
      if (!empty($entity_type)) {
        $entity_update_manager->uninstallEntityType($entity_type);
      }
    }

    if (!$this->isNew()) {
      $this->entityManager()->getStorage($this->entityTypeId)->delete([$this->id() => $this]);
    }
  }

}
