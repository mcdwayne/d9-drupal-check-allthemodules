<?php

namespace Drupal\entity_reference_integrity;

/**
 * An interface for generating field maps.
 */
interface DependencyFieldMapGeneratorInterface {

  /**
   * A field map keyed by the entity type being targeted by reference fields.
   *
   * The referential field map is in the format of:
   *
   * entity_type_id_being_referenced:
   *   source_entity_type_id:
   *     - referencing_field_a
   *     - referencing_field_b
   *
   * @return array
   *   A field map.
   */
  public function getReferentialFieldMap();

  /**
   * Get the segment of the field map for a specific entity type ID.
   *
   * @param string $entity_type_id
   *   An entity type ID.
   *
   * @return array
   *   A segment of the field map.
   */
  public function getReferencingFields($entity_type_id);

}
