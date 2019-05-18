<?php

namespace Drupal\entity_pilot_map_config;

use Drupal\entity_pilot\ArrivalInterface;
use Drupal\entity_pilot\Data\FlightManifest;

/**
 * Defines a class for deciding the flow on arrival creation.
 */
class ArrivalCreationHandler {
  const BUNDLE_MAPPING_EDIT_ROUTE = 'entity.ep_bundle_mapping.edit_form';

  /**
   * Route name for selecting a mapping.
   */
  const ARRIVAL_MAPPING_SELECTION_ROUTE = 'entity.ep_arrival.mapping_form';

  /**
   * Route name for editing the field mapping.
   */
  const FIELD_MAPPING_EDIT_ROUTE = 'entity.ep_field_mapping.edit_form';

  /**
   * Difference manager mock.
   *
   * @var ConfigurationDifferenceManagerInterface
   */
  protected $differenceManager;

  /**
   * Mapping manager mock.
   *
   * @var MappingManagerInterface
   */
  protected $mappingManager;

  /**
   * Constructs a new ArrivalCreationHandler object.
   *
   * @param ConfigurationDifferenceManagerInterface $difference_manager
   *   Difference manager.
   * @param MappingManagerInterface $mapping_manager
   *   Mapping manager.
   */
  public function __construct(ConfigurationDifferenceManagerInterface $difference_manager, MappingManagerInterface $mapping_manager) {
    $this->differenceManager = $difference_manager;
    $this->mappingManager = $mapping_manager;
  }

  /**
   * Builds object containing logic to take for new arrival.
   *
   * @return \Drupal\entity_pilot_map_config\ArrivalCreationResult
   *   Result value object.
   */
  public function buildNewArrivalResult(ArrivalInterface $arrival) {
    $flight = FlightManifest::fromArrival($arrival);
    $field_mapping = $bundle_mapping = NULL;
    $destinations = [];
    $difference = $this->differenceManager->computeDifference($flight);
    if ($difference->requiresMapping()) {
      $bundle_redirect_required = FALSE;
      $field_redirect_required = FALSE;
      $selection_required = FALSE;
      $candidates = $this->mappingManager->loadForConfigurationDifference($difference);
      // The incoming flight has configuration differences.
      if ($difference->hasMissingBundles()) {
        if ($bundle_mappings = $candidates->getBundleMappings()) {
          $bundle_mapping = reset($bundle_mappings);
          if (count($bundle_mappings) > 1) {
            $selection_required = TRUE;
          }
        }
        else {
          $bundle_mapping = $this->mappingManager->createBundleMappingFromConfigurationDifference($difference, $flight);
          $bundle_redirect_required = TRUE;
        }
      }
      if ($difference->hasMissingFields()) {
        if ($field_mappings = $candidates->getFieldMappings()) {
          $field_mapping = reset($field_mappings);
          if (count($field_mappings) > 1) {
            $selection_required = TRUE;
          }
        }
        else {
          $field_mapping = $this->mappingManager->createFieldMappingFromConfigurationDifference($difference, $flight);
          $field_redirect_required = TRUE;
        }
      }
      if ($selection_required) {
        // More than one exists, so give user change to select.
        array_unshift($destinations, [
          'route_name' => self::ARRIVAL_MAPPING_SELECTION_ROUTE,
          'route_parameters' => [
            'ep_arrival' => $arrival->id(),
          ],
        ]);
      }
      if ($field_redirect_required && $field_mapping) {
        array_unshift($destinations, [
          'route_name' => self::FIELD_MAPPING_EDIT_ROUTE,
          'route_parameters' => [
            'ep_field_mapping' => $field_mapping->id(),
          ],
          'options' => [
            'query' => [
              'message' => TRUE,
            ],
          ],
        ]);
      }
      if ($bundle_redirect_required && $bundle_mapping) {
        array_unshift($destinations, [
          'route_name' => self::BUNDLE_MAPPING_EDIT_ROUTE,
          'route_parameters' => [
            'ep_bundle_mapping' => $bundle_mapping->id(),
          ],
          'options' => [
            'query' => [
              'message' => TRUE,
            ],
          ],
        ]);
      }
    }
    return new ArrivalCreationResult($bundle_mapping, $field_mapping, $destinations);
  }

}
