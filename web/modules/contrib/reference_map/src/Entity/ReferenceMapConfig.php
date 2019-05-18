<?php

namespace Drupal\reference_map\Entity;

use Symfony\Component\Yaml\Yaml;

/**
 * Defines the Map entity.
 *
 * @ConfigEntityType(
 *   id = "reference_map_config",
 *   label = @Translation("Map"),
 *   handlers = {
 *     "list_builder" = "Drupal\reference_map\ReferenceMapConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\reference_map\Form\ReferenceMapConfigForm",
 *       "edit" = "Drupal\reference_map\Form\ReferenceMapConfigForm",
 *       "delete" = "Drupal\reference_map\Form\ReferenceMapConfigDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id" = "id",
 *     "label" = "label",
 *     "type" = "type",
 *     "sourceType" = "sourceType",
 *     "sourceBundles" = "sourceBundles",
 *     "destinationType" = "destinationType",
 *     "map" = "map",
 *     "settings" = "settings"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/reference-map/{reference_map_config}",
 *     "delete-form" = "/admin/config/system/reference-map/{reference_map_config}/delete",
 *   },
 *   constraints = {
 *     "ReferenceMapMap" = {},
 *   }
 * )
 */
class ReferenceMapConfig extends ValidationConfigEntityBase implements ReferenceMapConfigInterface {

  /**
   * The Map ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Map label.
   *
   * @var string
   */
  protected $label;

  /**
   * The plugin type associated with this config.
   *
   * @var string
   */
  protected $type;

  /**
   * The map plugin type.
   *
   * @var string
   */
  protected $sourceType;

  /**
   * The allowed bundles of the source.
   *
   * @var array
   */
  protected $sourceBundles = [];

  /**
   * The entity type id of the destination entity.
   *
   * @var string
   */
  protected $destinationType;

  /**
   * The map array.
   *
   * @var array
   */
  protected $map = [];

  /**
   * Additional settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The Reference Map Type Manager service.
   *
   * @var \Drupal\reference_map\Plugin\ReferenceMapTypeManager
   */
  protected $referenceMapTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    $this->referenceMapTypeManager = \Drupal::service('plugin.manager.reference_map_type');
    parent::__construct($values, $entity_type);
  }

  /**
   * Represent the map as a string.
   *
   * @return string
   *   The map array as a yaml string.
   */
  public function __toString() {
    return Yaml::dump($this->map);
  }

  /**
   * {@inheritdoc}
   */
  public function setMap($map) {
    if (is_array($map)) {
      $this->map = $map;
    }
    elseif (is_string($map)) {
      $this->map = Yaml::parse($map);
    }
    else {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // Set the source.
    $this->sourceType = $this->map[0]['entity_type'];
    if (isset($this->map[0]['bundles'])) {
      $this->sourceBundles = $this->map[0]['bundles'];
    }
    else {
      $this->sourceBundles = [];
    }

    // Set the destination.
    $last = end($this->map);
    $this->destinationType = $last['entity_type'];

    // Reset the mapped fields cache for the map.
    reset($this->map);
    foreach ($this->map as $step) {
      $this->referenceMapTypeManager->resetMapStepsCache($step['entity_type']);
    }

    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($key, $value) {
    $this->settings[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    if (isset($this->settings[$key])) {
      return $this->settings[$key];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function __get($property) {
    return $this->$property;
  }

}
