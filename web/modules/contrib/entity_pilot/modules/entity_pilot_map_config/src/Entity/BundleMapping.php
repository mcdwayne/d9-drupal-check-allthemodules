<?php

namespace Drupal\entity_pilot_map_config\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\entity_pilot_map_config\BundleMappingInterface;

/**
 * Defines the Bundle mapping entity.
 *
 * @ConfigEntityType(
 *   id = "ep_bundle_mapping",
 *   label = @Translation("Bundle mapping"),
 *   handlers = {
 *     "list_builder" = "Drupal\entity_pilot_map_config\BundleMappingListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_pilot_map_config\Form\BundleMappingForm",
 *       "edit" = "Drupal\entity_pilot_map_config\Form\BundleMappingForm",
 *       "delete" = "Drupal\entity_pilot_map_config\Form\BundleMappingDeleteForm"
 *     }
 *   },
 *   config_prefix = "ep_bundle_mapping",
 *   admin_permission = "administer entity_pilot bundle mappings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/entity-pilot/bundle-mappings/{ep_bundle_mapping}",
 *     "edit-form" = "/admin/structure/entity-pilot/bundle-mappings/{ep_bundle_mapping}/edit",
 *     "delete-form" = "/admin/structure/entity-pilot/bundle-mappings/{ep_bundle_mapping}/delete",
 *     "collection" = "/admin/structure/entity-pilot/bundle-mappings"
 *   }
 * )
 */
class BundleMapping extends ConfigEntityBase implements BundleMappingInterface {
  /**
   * The Bundle mapping ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Bundle mapping label.
   *
   * @var string
   */
  protected $label;

  /**
   * The bundle mappings.
   *
   * @var array
   */
  protected $mappings = [];

  /**
   * {@inheritdoc}
   */
  public function addMapping(array $mapping) {
    $this->mappings[] = $mapping;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappings() {
    return $this->mappings;
  }

  /**
   * {@inheritdoc}
   */
  public function setMappings(array $mappings) {
    $this->mappings = $mappings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    foreach ($this->mappings as $mapping) {
      if ($mapping['destination_bundle_name'] === self::IGNORE_BUNDLE) {
        continue;
      }
      $entity_type = \Drupal::entityTypeManager()->getDefinition($mapping['entity_type']);
      if (($bundle = $entity_type->getBundleEntityType()) &&
        ($bundle_type = \Drupal::entityTypeManager()->getDefinition($bundle)) &&
        $bundle_type instanceof ConfigEntityType &&
        $prefix = $bundle_type->getConfigPrefix()) {
        $this->addDependency('config', $prefix . '.' . $mapping['destination_bundle_name']);
      }
    }
    return $this;
  }

}
