<?php

namespace Drupal\field_union\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines a class for a field union entity.
 *
 * @ConfigEntityType(
 *   id = "field_union",
 *   label = @Translation("Field Union"),
 *   label_collection = @Translation("Field unions"),
 *   label_singular = @Translation("field union"),
 *   label_plural = @Translation("field unions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count field union",
 *     plural = "@count field unions",
 *   ),
 *   config_prefix = "field_union",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "fields",
 *   }
 * )
 */
class FieldUnion extends ConfigEntityBase implements FieldUnionInterface {

  /**
   * The union ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label for the union.
   *
   * @var string
   */
  protected $label;

  /**
   * The union description.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Fields.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * Gets value of Description.
   *
   * @return string
   *   Value of Description.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Gets value of Fields.
   *
   * @return array
   *   Value of Fields.
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    \Drupal::service('plugin.manager.field.field_type')->clearCachedDefinitions();
    \Drupal::typedDataManager()->clearCachedDefinitions();
  }

}
