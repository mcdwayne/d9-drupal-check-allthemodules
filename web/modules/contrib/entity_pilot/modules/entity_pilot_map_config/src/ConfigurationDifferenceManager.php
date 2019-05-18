<?php

namespace Drupal\entity_pilot_map_config;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_pilot\Data\FlightManifestInterface;

/**
 * Computes the configuration differences between an incoming flight and a site.
 */
class ConfigurationDifferenceManager implements ConfigurationDifferenceManagerInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * Constructs a new ConfigurationDifferenceManager object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   Bundle info.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityBundleInfo = $entity_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function computeDifference(FlightManifestInterface $flight) {
    $field_differences = [];
    $bundle_differences = [];
    $flight_mapping = $flight->getFieldMapping();

    // Entity type differences.
    $flight_entity_type_ids = array_keys($flight_mapping);
    $site_entity_type_ids = array_keys($this->entityTypeManager->getDefinitions());
    $entity_type_differences = array_diff($flight_entity_type_ids, $site_entity_type_ids);

    // Bundle differences.
    $entities = $flight->getContents();
    $site_fields = [];
    foreach ($entities as $passenger) {
      $path = parse_url($passenger['_links']['type']['href'], PHP_URL_PATH);
      $parts = explode('/', $path);
      $bundle = array_pop($parts);
      $entity_type = array_pop($parts);
      if (in_array($entity_type, $entity_type_differences, TRUE)) {
        // This entity-type is missing.
        continue;
      }
      if (!in_array($bundle, array_keys($this->entityBundleInfo->getBundleInfo($entity_type)), TRUE)) {
        $bundle_differences[$entity_type][$bundle] = $bundle;
      }
      if (!isset($site_fields[$entity_type])) {
        try {
          foreach ($this->entityFieldManager->getFieldStorageDefinitions($entity_type) as $field) {
            $site_fields[$entity_type][$field->getName()] = $field->getType();
          }
        }
        catch (PluginNotFoundException $e) {
          // Ignore.
        }
      }
    }
    foreach ($flight_mapping as $entity_type => $fields) {
      if (in_array($entity_type, $entity_type_differences, TRUE)) {
        // This entity-type is missing.
        continue;
      }
      foreach ($fields as $field_name => $type) {
        if (!isset($site_fields[$entity_type][$field_name]) || $site_fields[$entity_type][$field_name] !== $type) {
          $field_differences[$entity_type][$field_name] = $type;
        }
      }
    }
    $bundle_differences = array_map('array_values', $bundle_differences);

    return new ConfigurationDifference($field_differences, $bundle_differences, array_values($entity_type_differences));
  }

}
