<?php

namespace Drupal\entity_pilot_map_config;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_pilot\Data\FlightManifestInterface;
use Drupal\entity_pilot_map_config\Entity\BundleMapping;
use Drupal\entity_pilot_map_config\Entity\FieldMapping;

/**
 * Defines a class for loading mappings that match a given difference.
 */
class MappingManager implements MappingManagerInterface {

  /**
   * Field mapping storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldMappingStorage;

  /**
   * Bundle mapping storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $bundleMappingStorage;

  /**
   * Constructs a new MappingManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->bundleMappingStorage = $entity_type_manager->getStorage('ep_bundle_mapping');
    $this->fieldMappingStorage = $entity_type_manager->getStorage('ep_field_mapping');
  }

  /**
   * {@inheritdoc}
   */
  public function loadForConfigurationDifference(ConfigurationDifferenceInterface $configuration_difference) {
    $field_mappings = [];
    $bundle_mappings = [];
    if ($field_difference = $configuration_difference->getMissingFields()) {
      /** @var \Drupal\entity_pilot_map_config\FieldMappingInterface $field_mapping */
      foreach ($this->fieldMappingStorage->loadMultiple() as $id => $field_mapping) {
        $mapping_fields = [];
        foreach ($field_mapping->getMappings() as $mapping) {
          $mapping_fields[$mapping['entity_type']][$mapping['source_field_name']] = $mapping['field_type'];
        }
        foreach ($field_difference as $entity_type => $fields) {
          if (!isset($mapping_fields[$entity_type]) || array_diff_assoc($fields, $mapping_fields[$entity_type])) {
            // Not all fields are covered, continue to next field mapping.
            continue 2;
          }
        }
        $field_mappings[$field_mapping->id()] = $field_mapping;
      }
    }
    if ($bundle_difference = $configuration_difference->getMissingBundles()) {
      /** @var \Drupal\entity_pilot_map_config\BundleMappingInterface $bundle_mapping */
      foreach ($this->bundleMappingStorage->loadMultiple() as $id => $bundle_mapping) {
        $mapping_bundles = [];
        foreach ($bundle_mapping->getMappings() as $mapping) {
          $mapping_bundles[$mapping['entity_type']][] = $mapping['source_bundle_name'];
        }
        foreach ($bundle_difference as $entity_type => $bundles) {
          if (!isset($mapping_bundles[$entity_type]) || array_diff($bundles, $mapping_bundles[$entity_type])) {
            // Not all bundles are covered, continue to next bundle mapping.
            continue 2;
          }
        }
        $bundle_mappings[$bundle_mapping->id()] = $bundle_mapping;
      }
    }
    return new MatchingMappingsResult($bundle_mappings, $field_mappings);
  }

  /**
   * {@inheritdoc}
   */
  public function createBundleMappingFromConfigurationDifference(ConfigurationDifferenceInterface $configuration_difference, FlightManifestInterface $flight_manifest) {
    $bundle_mapping = [];
    foreach ($configuration_difference->getMissingBundles() as $entity_type => $bundles) {
      foreach ($bundles as $bundle) {
        $bundle_mapping[] = [
          'entity_type' => $entity_type,
          'source_bundle_name' => $bundle,
          'destination_bundle_name' => BundleMappingInterface::IGNORE_BUNDLE,
        ];
      }
    }
    $mapping = BundleMapping::create([
      'id' => 'flight_' . $flight_manifest->getRemoteId() . '_account_' . $flight_manifest->getCarrierId(),
      'label' => $flight_manifest->getSite() ?: sprintf('Flight % : account %', $flight_manifest->getRemoteId(), $flight_manifest->getCarrierId()),
      'mappings' => $bundle_mapping,
    ]);
    $mapping->save();
    return $mapping;
  }

  /**
   * {@inheritdoc}
   */
  public function createFieldMappingFromConfigurationDifference(ConfigurationDifferenceInterface $configuration_difference, FlightManifestInterface $flight_manifest) {
    $field_mapping = [];
    foreach ($configuration_difference->getMissingFields() as $entity_type => $fields) {
      foreach ($fields as $field_name => $field_type) {
        $field_mapping[] = [
          'entity_type' => $entity_type,
          'source_field_name' => $field_name,
          'destination_field_name' => FieldMappingInterface::IGNORE_FIELD,
          'field_type' => $field_type,
        ];
      }
    }
    $mapping = FieldMapping::create([
      'id' => 'flight_' . $flight_manifest->getRemoteId() . '_account_' . $flight_manifest->getCarrierId(),
      'label' => $flight_manifest->getSite() ?: sprintf('Flight % : account %', $flight_manifest->getRemoteId(), $flight_manifest->getCarrierId()),
      'mappings' => $field_mapping,
    ]);
    $mapping->save();
    return $mapping;
  }

}
