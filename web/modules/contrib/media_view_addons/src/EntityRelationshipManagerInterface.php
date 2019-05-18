<?php

namespace Drupal\media_view_addons;

/**
 * Manage relationships between entities.
 */
interface EntityRelationshipManagerInterface {
  /**
   * Get all top level nodes from their referenced entities.
   *
   * @param $entity_type_id
   * @param $entity_id
   * @param int $nesting_level
   * @param int $nesting_limit
   * @return array
   */
  public function topLevelNids($entity_type_id, $entity_id, $nesting_level = 0, $nesting_limit = 5);
}
