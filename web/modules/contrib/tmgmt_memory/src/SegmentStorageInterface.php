<?php

namespace Drupal\tmgmt_memory;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines a common interface for Segment entity controller classes.
 */
interface SegmentStorageInterface extends EntityStorageInterface {

  /**
   * Loads multiple entities using the data field and the langcode.
   *
   * @param string $language
   *   The langcode of the language.
   * @param string $stripped_data
   *   The data of the entity to load without HTML tags.
   *
   * @return \Drupal\tmgmt_memory\SegmentInterface[]
   *   An array of Segment objects. Returns an empty array if no
   *   matching entities are found.
   */
  public function loadMultipleByLanguageAndData($language, $stripped_data);

  /**
   * Loads one entity using the data field and the langcode.
   *
   * @param string $language
   *   The langcode of the language.
   * @param string $stripped_data
   *   The data of the entity to load without HTML tags.
   *
   * @return \Drupal\tmgmt_memory\SegmentInterface|null
   *   An entity object. NULL if no matching entity is found.
   */
  public function loadByLanguageAndData($language, $stripped_data);

}
