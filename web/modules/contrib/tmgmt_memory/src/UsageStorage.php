<?php

namespace Drupal\tmgmt_memory;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Controller class for Usages.
 *
 * This extends the default content entity storage class,
 * adding required special handling for Usage entities.
 */
class UsageStorage extends SqlContentEntityStorage implements UsageStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadByIdentifiers($job_item_id, $data_item_key, $segment_delta, $segment_id) {
    $query = \Drupal::entityQuery('tmgmt_memory_usage');
    $and = $query->andConditionGroup()
      ->condition('job_item_id', $job_item_id)
      ->condition('data_item_key', $data_item_key)
      ->condition('segment_delta', $segment_delta)
      ->condition('segment_id', $segment_id);
    $query->condition($and);
    $results = $query->execute();
    $result = reset($results);
    return $result ? $this->load($result) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByLanguageAndData($language, $data) {
    $stripped_data = strip_tags($data);
    $query = \Drupal::entityQuery('tmgmt_memory_segment');
    $and = $query->andConditionGroup()
      ->condition('language', $language)
      ->condition('stripped_data', $stripped_data);
    $query->condition($and);
    $segment_ids = $query->execute();
    if (empty($segment_ids)) {
      return [];
    }
    $segment_id = reset($segment_ids);

    $query = \Drupal::entityQuery('tmgmt_memory_usage');
    $and = $query->andConditionGroup()
      ->condition('segment_id', $segment_id)
      ->condition('data', $data);
    $query->condition($and);
    return $this->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleBySegment($segment_id) {
    $query = \Drupal::entityQuery('tmgmt_memory_usage');
    $query->condition('segment_id', $segment_id);
    return $this->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleBySourceLanguage($source_language) {
    $query = \Drupal::entityQuery('tmgmt_memory_segment');
    $query->condition('language', $source_language);
    $source_segments = $query->execute();

    $query = \Drupal::entityQuery('tmgmt_memory_usage');
    $query->condition('segment_id', $source_segments, 'IN');
    return $this->loadMultiple($query->execute());
  }

}
