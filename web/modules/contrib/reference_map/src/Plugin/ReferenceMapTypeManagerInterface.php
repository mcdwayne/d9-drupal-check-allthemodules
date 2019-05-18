<?php

namespace Drupal\reference_map\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides an interface for Reference Map Type managers.
 */
interface ReferenceMapTypeManagerInterface extends PluginManagerInterface {

  /**
   * Gets a structured array of all reference map steps for this entity type.
   *
   * @param string $entity_type_id
   *   The entity type id to get map steps for.
   *
   * @return array
   *   An associative array with keys of all Reference Map Type plugin ids that
   *   have Reference Map Config entities that have steps that have the passed
   *   in entity type.
   *   - $plugin_id: An associative array with keys of all Reference Map Config
   *     entity ids that have steps that have the passed in entity type.
   *     - $map_id: An associative array with keys of all step positions of
   *       steps that have the passed in entity type.
   *       - An associative array containing the following keys:
   *         - entity_type: The entity type that the step applies to.
   *         - bundles: (optional) An indexed array of bundles that the step
   *           applies to. If not provided, the step applies to all bundles.
   *         - field_name: The field referencing the entity in the next step.
   */
  public function getMapStepsForEntityType($entity_type_id);

  /**
   * Clear a cache of mapped fields.
   *
   * @param string $entity_type_id
   *   The mapped fields cache record to clear.
   */
  public function resetMapStepsCache($entity_type_id);

}
