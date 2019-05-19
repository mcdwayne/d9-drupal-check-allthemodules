<?php

namespace Drupal\tmgmt_memory;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for tmgmt_memory_segment entity.
 *
 * @ingroup tmgmt_memory
 */
interface SegmentInterface extends ContentEntityInterface {

  /**
   * Return the language of the Segment.
   *
   * @return \Drupal\Core\Language\Language
   *   The language.
   */
  public function getLanguage();

  /**
   * Return the langcode of the Segment.
   *
   * @return string
   *   The langcode of the language.
   */
  public function getLangcode();

  /**
   * Return the data of the Segment without HTML tags.
   *
   * @return string
   *   The data of the Segment.
   */
  public function getStrippedData();

  /**
   * Return the number of usages of this Segment.
   *
   * @return int
   *   The number of usages of the Segment.
   */
  public function countUsages();

  /**
   * Increment the counter_usages.
   */
  public function incrementCounterUsages();

}
