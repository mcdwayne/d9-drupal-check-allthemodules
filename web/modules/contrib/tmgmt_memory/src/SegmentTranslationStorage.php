<?php

namespace Drupal\tmgmt_memory;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Controller class for Segment Translations.
 *
 * This extends the default content entity storage class,
 * adding required special handling for SegmentTranslation entities.
 */
class SegmentTranslationStorage extends SqlContentEntityStorage implements SegmentTranslationStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByLanguageAndData($source_language, $source_stripped_data, $target_language, $target_stripped_data = NULL) {
    /** @var \Drupal\tmgmt_memory\SegmentStorageInterface $segment_storage */
    $segment_storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment');
    $source_segment = $segment_storage->loadByLanguageAndData($source_language, $source_stripped_data);
    if (!$source_segment) {
      return [];
    }

    $target_segment = NULL;
    if ($target_stripped_data) {
      $target_segment = $segment_storage->loadByLanguageAndData($target_language, $target_stripped_data);
      if (!$target_segment) {
        return [];
      }
    }

    $query = \Drupal::entityQuery('tmgmt_memory_segment_translation');
    $and = $query->andConditionGroup()
      ->condition('source', $source_segment->id())
      ->condition('target_language', $target_language);
    if ($target_segment) {
      $and->condition('target', $target_segment->id());
    }
    $query->condition($and);
    $query->sort('quality', 'DESC');
    return $this->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function loadByLanguageAndData($source_language, $source_stripped_data, $target_language, $target_stripped_data = NULL) {
    $results = $this->loadMultipleByLanguageAndData($source_language, $source_stripped_data, $target_language, $target_stripped_data);
    $result = reset($results);
    return $result ? $result : NULL;
  }

}
