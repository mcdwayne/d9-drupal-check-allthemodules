<?php

namespace Drupal\tmgmt_memory;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Controller class for Segments.
 *
 * This extends the default content entity storage class,
 * adding required special handling for Segment entities.
 */
class SegmentStorage extends SqlContentEntityStorage implements SegmentStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByLanguageAndData($language, $stripped_data) {
    $query = \Drupal::entityQuery('tmgmt_memory_segment');
    $and = $query->andConditionGroup()
      ->condition('language', $language)
      ->condition('stripped_data', $stripped_data);
    $query->condition($and);
    return $this->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function loadByLanguageAndData($language, $stripped_data) {
    $results = $this->loadMultipleByLanguageAndData($language, $stripped_data);
    $result = reset($results);
    return $result ? $result : NULL;
  }

}
