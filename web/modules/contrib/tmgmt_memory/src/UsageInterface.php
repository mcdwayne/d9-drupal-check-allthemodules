<?php

namespace Drupal\tmgmt_memory;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for tmgmt_memory_usage entity.
 *
 * @ingroup tmgmt_memory
 */
interface UsageInterface extends ContentEntityInterface {

  /**
   * Return the Job Item ID of the Usage.
   *
   * @return int
   *   The Job Item ID.
   */
  public function getJobItemId();

  /**
   * Return the Job Item of the Usage.
   *
   * @return \Drupal\tmgmt\JobItemInterface
   *   The Job Item entity.
   */
  public function getJobItem();

  /**
   * Return Data Item key in the Job Item.
   *
   * @return string
   *   The Data Item Key.
   */
  public function getDataItemKey();

  /**
   * Return Segment Delta identifier in the Data Item.
   *
   * @return string
   *   The Segment delta.
   */
  public function getSegmentDelta();

  /**
   * Return the segment ID of the Usage.
   *
   * @return int
   *   The Segment ID.
   */
  public function getSegmentId();

  /**
   * Return the segment of the Usage.
   *
   * @return \Drupal\tmgmt_memory\SegmentInterface
   *   The Segment entity.
   */
  public function getSegment();

  /**
   * Return the data of the Usage with HTML tags.
   *
   * @return string
   *   The data of the Usage.
   */
  public function getData();

  /**
   * Return the context data of the Usage.
   *
   * @return array
   *   The context data of the Usage.
   */
  public function getContextData();

  /**
   * Return the language of the Usage.
   *
   * @return \Drupal\Core\Language\Language
   *   The language.
   */
  public function getLanguage();

  /**
   * Return the langcode of the Usage.
   *
   * @return string
   *   The langcode of the language.
   */
  public function getLangcode();

}
