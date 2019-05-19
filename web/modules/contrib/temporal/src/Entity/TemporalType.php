<?php

/**
 * @file
 * Contains \Drupal\temporal\Entity\TemporalType.
 */

namespace Drupal\temporal\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\temporal\TemporalTypeInterface;

/**
 * Defines the Temporal type entity.
 *
 * @ConfigEntityType(
 *   id = "temporal_type",
 *   label = @Translation("Temporal type"),
 *   handlers = {
 *     "list_builder" = "Drupal\temporal\TemporalTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\temporal\Form\TemporalTypeForm",
 *       "edit" = "Drupal\temporal\Form\TemporalTypeForm",
 *       "delete" = "Drupal\temporal\Form\TemporalTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\temporal\TemporalTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "temporal_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "temporal",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/temporal_type/{temporal_type}",
 *     "add-form" = "/temporal_type/add",
 *     "edit-form" = "/temporal_type/{temporal_type}/edit",
 *     "delete-form" = "/temporal_type/{temporal_type}/delete",
 *     "collection" = "/temporal_type"
 *   }
 * )
 */
class TemporalType extends ConfigEntityBundleBase implements TemporalTypeInterface {

  /**
   * The Temporal type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Temporal type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Temporal type description.
   *
   * @var string
   */
  public $description;

  /**
   * The concatenated entity type config string
   *
   * @var string
   */
  protected $field_to_track;

  /**
   * The type of tracking, historical or audit based
   *
   * @var string
   */
  protected $tracking_type;

  /**
   * The Entity type to be tracked
   *
   * @var string
   */
  protected $temporalEntityType;

  /**
   * The Bundle type to be tracked
   *
   * @var string
   */
  protected $temporalEntityBundle;

  /**
   * The Field Type to be tracked
   *
   * @var string
   */
  protected $temporalEntityFieldType;

  /**
   * The Field type to be tracked
   *
   * @var string
   */
  protected $temporalEntityField;

  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->parse_fieldtotrack();
  }

  /**
   * @return string
   */
  public function getFieldToTrack() {
    return $this->field_to_track;
  }

  /**
   * @param string $field_to_track
   */
  public function setFieldToTrack($field_to_track) {
    $this->field_to_track = $field_to_track;
  }

  public function getFieldName() {
    $components = explode('__', $this->getFieldToTrack());
    return end($components);
  }

  /**
   * Return the tracking type set for a temporal type, default to historical
   *
   * @return string
   */
  public function getTrackingType() {
    return $this->tracking_type ? $this->tracking_type : 'historical';
  }

  /**
   * @param string $tracking_type
   */
  public function setTrackingType($tracking_type) {
    $this->tracking_type = $tracking_type;
  }

  /*
   * The fieldtotrack key is comprised of:
   * entity__bundle__field_type__field_name
   */
  private function parse_fieldtotrack() {
    if($this->field_to_track) {
      $properties = explode('__', $this->field_to_track);
      $this->setTemporalEntityType($properties[0]);
      $this->setTemporalEntityBundle($properties[1]);
      $this->setTemporalEntityFieldType($properties[2]);
      $this->setTemporalEntityField($properties[3]);
    }
  }

  /**
   * @return mixed
   */
  public function getTemporalEntityType() {
    return $this->temporalEntityType;
  }

  /**
   * @param mixed $entityType
   */
  public function setTemporalEntityType($entityType) {
    $this->temporalEntityType = $entityType;
  }

  /**
   * @return mixed
   */
  public function getTemporalEntityBundle() {
    return $this->temporalEntityBundle;
  }

  /**
   * @param mixed $entityBundle
   */
  public function setTemporalEntityBundle($entityBundle) {
    $this->temporalEntityBundle = $entityBundle;
  }

  /**
   * @return mixed
   */
  public function getTemporalEntityFieldType() {
    return $this->temporalEntityFieldType;
  }

  /**
   * @param mixed $entityFieldType
   */
  public function setTemporalEntityFieldType($entityFieldType) {
    $this->temporalEntityFieldType = $entityFieldType;
  }

  /**
   * @return mixed
   */
  public function getTemporalEntityField() {
    return $this->temporalEntityField;
  }

  /**
   * @param mixed $entityField
   */
  public function setTemporalEntityField($entityField) {
    $this->temporalEntityField = $entityField;
  }
}
