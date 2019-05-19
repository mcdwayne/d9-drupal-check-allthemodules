<?php

namespace Drupal\tmgmt_memory;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Controller class for Usage Translations.
 *
 * This extends the default content entity storage class,
 * adding required special handling for UsageTranslation entities.
 */
class UsageTranslationStorage extends SqlContentEntityStorage implements UsageTranslationStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByLanguageAndData($source_language, $source_data, $target_language, $target_data = NULL) {
    /** @var \Drupal\tmgmt_memory\UsageStorageInterface $usage_storage */
    $usage_storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_usage');
    $source_usages = $usage_storage->loadMultipleByLanguageAndData($source_language, $source_data);
    if (empty($source_usages)) {
      return [];
    }

    $target_usages = [];
    if ($target_data) {
      $target_usages = $usage_storage->loadMultipleByLanguageAndData($target_language, $target_data);
      if (empty($target_usages)) {
        return [];
      }
    }

    $query = \Drupal::entityQuery('tmgmt_memory_usage_translation');
    $and = $query->andConditionGroup()
      ->condition('source', array_map(function (&$value){
        return $value->id();
      }, $source_usages), 'IN')
      ->condition('target_language', $target_language);
    if (!empty($target_usages)) {
      $and->condition('target', array_map(function (&$value){
        return $value->id();
      }, $target_usages), 'IN');
    }
    $and->condition('state', TRUE);
    $query->condition($and);
    $query->sort('quality', 'DESC');
    return $this->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function loadBestMatchByLanguageAndData($source_language, $source_data, $target_language, $target_data = NULL) {
    $results = $this->loadMultipleByLanguageAndData($source_language, $source_data, $target_language, $target_data);
    $result = reset($results);
    return $result ? $result : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleBySourcesAndTargets(array $sources = NULL, array $targets = NULL) {
    $query = \Drupal::entityQuery('tmgmt_memory_usage_translation');
    $and = $query->andConditionGroup();
    if ($sources) {
      $and->condition('source', array_map(function (&$value) {
        return $value->id();
      }, $sources), 'IN');
    }
    if ($targets) {
      $and->condition('target', array_map(function (&$value) {
        return $value->id();
      }, $targets), 'IN');
    }
    $query->condition($and);
    return $this->loadMultiple($query->execute());
  }

}
