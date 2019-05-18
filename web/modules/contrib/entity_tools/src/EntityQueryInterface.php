<?php

namespace Drupal\entity_tools;

/**
 * Class EntityFilter.
 *
 * @package Drupal\entity_tools
 */
interface EntityQueryInterface {

  /**
   * Determines if an entity is multilingual.
   *
   * @return bool
   *   Is multilingual.
   */
  public function isEntityMultilingual();

  /**
   * Filter by a single Content Entity type.
   *
   * Set type acts as a normalizer between entity types.
   * - Node: type, bundle, content type
   * - Taxonomy Term: vocabulary
   * - ...
   *
   * @param string $type
   *   The Content Entity type.
   */
  public function setType($type);

  /**
   * Filters by multiple Content Entity types.
   *
   * Produces a or condition.
   *
   * Set type acts as a normalizer between entity types.
   * - Node: type, bundle, content type
   * - Taxonomy Term: vocabulary
   * - ...
   *
   * @param array $types
   *   The Content Entity types.
   */
  public function setTypes(array $types);

  /**
   * Overrides the default language negotiation.
   *
   * @param string $id
   *   Language id.
   */
  public function setLanguage($id);

  /**
   * Execute the query.
   *
   * @return int|array
   *   Returns an integer for count queries or an array of ids. The values of
   *   the array are always entity ids. The keys will be revision ids if the
   *   entity supports revision and entity ids if not.
   */
  public function execute();

}
