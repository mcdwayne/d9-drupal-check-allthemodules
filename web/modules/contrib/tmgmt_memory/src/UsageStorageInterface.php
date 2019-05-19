<?php

namespace Drupal\tmgmt_memory;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines a common interface for Segment entity controller classes.
 */
interface UsageStorageInterface extends EntityStorageInterface {

  /**
   * Loads one entity using the four identifiers.
   *
   * @param int $job_item_id
   *   The Job Item ID.
   * @param string $data_item_key
   *   The Data Item key.
   * @param int $segment_delta
   *   The Segment delta.
   * @param int $segment_id
   *   The Segment ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An entity object. NULL if no matching entity is found.
   */
  public function loadByIdentifiers($job_item_id, $data_item_key, $segment_delta, $segment_id);

  /**
   * Loads multiple entities using the data field and the langcode.
   *
   * @param string $language
   *   The langcode of the language.
   * @param string $data
   *   The data of the entity to load with HTML tags.
   *
   * @return \Drupal\tmgmt_memory\UsageInterface[]
   *   An array of Usage objects. Returns an empty array if no
   *   matching entities are found.
   */
  public function loadMultipleByLanguageAndData($language, $data);

  /**
   * Loads multiple entities using the segment id.
   *
   * @param int $segment_id
   *   The segment ID.
   *
   * @return \Drupal\tmgmt_memory\UsageInterface[]
   *   An array of Usage objects. Returns an empty array if no
   *   matching entities are found.
   */
  public function loadMultipleBySegment($segment_id);

  /**
   * Loads the usage using the source language.
   *
   * @param string $source_language
   *   The source language.
   *
   * @return \Drupal\tmgmt_memory\UsageInterface[]
   *   An array of Usage objects. Returns an empty array if no matching
   *   entities are found.
   */
  public function loadMultipleBySourceLanguage($source_language);

}
