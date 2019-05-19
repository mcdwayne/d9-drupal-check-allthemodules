<?php

namespace Drupal\smallads;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Storage controller for smallads.
 */
class SmalladStorage extends SqlContentEntityStorage {

  /**
   * Count the number of smallads in a given category.
   *
   * @param int $term_id
   *   The entity id of a taxonomy term.
   *
   * @return int
   *   The number of smallads in that category
   */
  public function count($term_id = NULL) {
    $query = $this->entityQuery('smallad')->count();
    if ($term_id) {
      $query->condition(SMALLAD_CATEGORIES, $term_id);
    }
    return $query->execute();
  }

}
