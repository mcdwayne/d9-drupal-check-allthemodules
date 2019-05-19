<?php

namespace Drupal\tmgmt_memory;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines a common interface for Segment Translation entity controller classes.
 */
interface SegmentTranslationStorageInterface extends EntityStorageInterface {

  /**
   * Loads multiple entities using the data field and the langcodes.
   *
   * @param string $source_language
   *   The langcode of the source language.
   * @param string $source_stripped_data
   *   The source data without HTML tags.
   * @param string $target_language
   *   The langcode of the target language.
   * @param string $target_stripped_data
   *   (Optional) The source data without HTML tags.
   *
   * @return \Drupal\tmgmt_memory\SegmentTranslationInterface[]
   *   An array of SegmentTranslation objects. Returns an empty array if no
   *   matching entities are found.
   */
  public function loadMultipleByLanguageAndData($source_language, $source_stripped_data, $target_language, $target_stripped_data = NULL);

  /**
   * Loads one entity using the data field and the source and target langcodes.
   *
   * If $target_stripped_data is not provided, and there is more than one
   * translation of the source segment to the target language, this will return
   * the translation with better quality.
   *
   * @param string $source_language
   *   The langcode of the source language.
   * @param string $source_stripped_data
   *   The source data without HTML tags.
   * @param string $target_language
   *   The langcode of the target language.
   * @param string $target_stripped_data
   *   (Optional) The source data without HTML tags.
   *
   * @return \Drupal\tmgmt_memory\SegmentTranslationInterface|null
   *   A SegmentTranslation object. NULL if no matching entity is found.
   */
  public function loadByLanguageAndData($source_language, $source_stripped_data, $target_language, $target_stripped_data = NULL);

}
