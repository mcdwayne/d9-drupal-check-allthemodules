<?php

namespace Drupal\tmgmt_memory;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines a common interface for Usage Translation entity controller classes.
 */
interface UsageTranslationStorageInterface extends EntityStorageInterface {

  /**
   * Loads multiple entities using the data field and the langcodes.
   *
   * @param string $source_language
   *   The langcode of the source language.
   * @param string $source_data
   *   The source data with HTML tags.
   * @param string $target_language
   *   The langcode of the target language.
   * @param string $target_data
   *   (Optional) The target data with HTML tags.
   *
   * @return \Drupal\tmgmt_memory\UsageTranslationInterface[]
   *   An array of UsageTranslation objects. Returns an empty array if no
   *   matching entities are found.
   */
  public function loadMultipleByLanguageAndData($source_language, $source_data, $target_language, $target_data = NULL);

  /**
   * Loads the best translation using the langcode and the data.
   *
   * @param string $source_language
   *   The langcode of the source language.
   * @param string $source_data
   *   The source data with HTML tags.
   * @param string $target_language
   *   The langcode of the target language.
   * @param string $target_data
   *   (Optional) The target data with HTML tags.
   *
   * @return \Drupal\tmgmt_memory\UsageTranslationInterface|null
   *   A UsageTranslation object. NULL if no matching entity is found.
   */
  public function loadBestMatchByLanguageAndData($source_language, $source_data, $target_language, $target_data = NULL);

  /**
   * Loads the translation using the source and target usages.
   *
   * If no sources or targets are provided, will return all the
   * UsageTranslations.
   *
   * @param \Drupal\tmgmt_memory\UsageInterface[] $sources
   *   (Optional) The sources. If not set, will filter using just the targets.
   * @param \Drupal\tmgmt_memory\UsageInterface[] $targets
   *   (Optional) The targets. If not set, will filter using just the sources.
   *
   * @return \Drupal\tmgmt_memory\UsageTranslationInterface[]
   *   An array of UsageTranslation objects. Returns an empty array if no
   *   matching entities are found.
   */
  public function loadMultipleBySourcesAndTargets(array $sources = NULL, array $targets = NULL);

}
