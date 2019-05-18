<?php

namespace Drupal\entity_pilot_map_config;

use Drupal\rest\LinkManager\LinkManagerInterface;

/**
 * Mapping handler service for applying config mapping to incoming passengers.
 */
class MappingHandler implements MappingHandlerInterface {

  /**
   * Link manager service.
   *
   * @var \Drupal\rest\LinkManager\LinkManagerInterface
   */
  protected $linkManager;

  /**
   * Constructs a new MappingHandler object.
   *
   * @param \Drupal\rest\LinkManager\LinkManagerInterface $link_manager
   *   Type link manager service.
   */
  public function __construct(LinkManagerInterface $link_manager) {
    $this->linkManager = $link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applyMappingPair(array $passengers, FieldMappingInterface $field_mapping, BundleMappingInterface $bundle_mapping) {
    $bundle_mappings_by_entity_type = [];
    $field_mappings_by_entity_type = [];
    foreach ($bundle_mapping->getMappings() as $mapping) {
      $bundle_mappings_by_entity_type[$mapping['entity_type']][$mapping['source_bundle_name']] = $mapping['destination_bundle_name'];
    }
    foreach ($field_mapping->getMappings() as $mapping) {
      $field_mappings_by_entity_type[$mapping['entity_type']][$mapping['source_field_name']] = $mapping['destination_field_name'];
    }
    foreach ($passengers as $uuid => $passenger) {
      $path = parse_url($passenger['_links']['type']['href'], PHP_URL_PATH);
      $parts = explode('/', $path);
      $bundle = array_pop($parts);
      $entity_type = array_pop($parts);
      $new_bundle = $bundle;
      // Update bundles.
      if (isset($bundle_mappings_by_entity_type[$entity_type][$bundle])) {
        $new_bundle = $bundle_mappings_by_entity_type[$entity_type][$bundle];
        if ($new_bundle === BundleMappingInterface::IGNORE_BUNDLE) {
          unset($passengers[$uuid]);
        }
        else {
          $passengers[$uuid]['_links']['type']['href'] = $this->linkManager->getTypeUri($entity_type, $new_bundle);
        }
      }
      if (isset($field_mappings_by_entity_type[$entity_type])) {
        foreach ($field_mappings_by_entity_type[$entity_type] as $old_field => $new_field) {
          // Update simple fields.
          if (isset($passenger[$old_field])) {
            if ($new_field !== FieldMappingInterface::IGNORE_FIELD) {
              $passengers[$uuid][$new_field] = $passenger[$old_field];
            }
            unset($passengers[$uuid][$old_field]);
          }
          // Update relation fields.
          $old_relation_uri = $this->linkManager->getRelationUri($entity_type, $bundle, $old_field);
          $new_relation_uri = FALSE;
          if ($new_field !== FieldMappingInterface::IGNORE_FIELD) {
            $new_relation_uri = $this->linkManager->getRelationUri($entity_type, $new_bundle, $new_field);
          }
          if (isset($passenger['_embedded'][$old_relation_uri])) {
            if ($new_relation_uri) {
              $passengers[$uuid]['_embedded'][$new_relation_uri] = $passenger['_embedded'][$old_relation_uri];
            }
            unset($passengers[$uuid]['_embedded'][$old_relation_uri]);
          }
        }
      }
    }
    return $passengers;
  }

}
