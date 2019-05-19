<?php

namespace Drupal\tmgmt_memory;

/**
 * This Service offers a suite of methods to manage the memory.
 */
class MemoryManager {

  /**
   * Add a new segment.
   *
   * It will just create the segment if it does not already exist, otherwise
   * will return the existing one.
   *
   * @param string $language
   *   The langcode of the language.
   * @param string $stripped_data
   *   The data of the segment without HTML tags.
   *
   * @return \Drupal\tmgmt_memory\SegmentInterface
   *   The new Segment or the existing one.
   */
  public function addSegment($language, $stripped_data) {
    $segment = $this->getSegment($language, $stripped_data);
    if ($segment) {
      return $segment;
    }
    $segment = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment')->create([
      'language' => $language,
      'stripped_data' => $stripped_data,
    ]);
    $segment->save();
    return $segment;
  }

  /**
   * Get a segment using the langcode and the data without HTML tags.
   *
   * @param string $language
   *   The langcode of the language.
   * @param string $stripped_data
   *   The data of the segment without HTML tags.
   *
   * @return \Drupal\tmgmt_memory\SegmentInterface|NULL
   *   The Segment or NULL if the segment does not exist.
   */
  public function getSegment($language, $stripped_data) {
    /** @var \Drupal\tmgmt_memory\SegmentStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment');
    return $storage->loadByLanguageAndData($language, $stripped_data);
  }

  /**
   * Add a new usage.
   *
   * It will just create the new usage if it does not already exist, otherwise
   * will return the existing one.
   * If there is no Segment for this usage, a new one will also be created.
   *
   * @param string $language
   *   The langcode of the language.
   * @param string $data
   *   The data of the usage with HTML tags.
   * @param int $job_item_id
   *   The Job Item ID.
   * @param string $data_item_key
   *   The key of the data item in the Job Item.
   * @param int $segment_delta
   *   The segment delta in the data item.
   * @param array $context_data
   *   (Optional) The context data of the usage.
   *
   * @return \Drupal\tmgmt_memory\UsageInterface
   *   The new Usage or the existing one.
   */
  public function addUsage($language, $data, $job_item_id, $data_item_key, $segment_delta, $context_data = []) {
    /** @var \Drupal\tmgmt_memory\SegmentStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment');
    $stripped_data = strip_tags($data);
    $segment = $storage->loadByLanguageAndData($language, $stripped_data);
    if (!isset($segment)) {
      $segment = $this->addSegment($language, $stripped_data);
    }

    $usage = $this->getUsage($job_item_id, $data_item_key, $segment_delta, $segment->id());
    if ($usage) {
      return $usage;
    }
    $usage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_usage')->create([
      'job_item_id' => $job_item_id,
      'data_item_key' => $data_item_key,
      'segment_delta' => $segment_delta,
      'segment_id' => $segment->id(),
      'data' => $data,
      'context_data' => $context_data,
    ]);
    $usage->save();
    return $usage;
  }

  /**
   * Get a usage using its four identifiers.
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
   * @return \Drupal\tmgmt_memory\UsageInterface|null
   *   An Usage object. NULL if no matching entity is found.
   */
  public function getUsage($job_item_id, $data_item_key, $segment_delta, $segment_id) {
    /** @var \Drupal\tmgmt_memory\UsageStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_usage');
    return $storage->loadByIdentifiers($job_item_id, $data_item_key, $segment_delta, $segment_id);
  }

  /**
   * Add a new translation of a segment.
   *
   * It will just create the new segment translation if it does not already
   * exist, otherwise will return the existing one.
   * If there is no Segment for this translation (source or target), a new one
   * will also be created.
   *
   * @param string $source_language
   *   The langcode of the source language.
   * @param string $source_stripped_data
   *   The data of the source segment without HTML tags.
   * @param string $target_language
   *   The langcode of the target language.
   * @param string $target_stripped_data
   *   The data of the target segment with HTML tags.
   * @param int $quality
   *   (Optional) The quality from 0 to 5.
   * @param bool $state
   *   (Optional) The state of the translation, TRUE if enabled (default),
   *   FALSE otherwise.
   *
   * @return \Drupal\tmgmt_memory\SegmentTranslationInterface
   *   The new SegmentTranslation or the existing one.
   */
  public function addSegmentTranslation($source_language, $source_stripped_data, $target_language, $target_stripped_data, $quality = NULL, $state = TRUE) {
    $segment_translation = $this->getSegmentTranslation($source_language, $source_stripped_data, $target_language, $target_stripped_data);
    if ($segment_translation) {
      return $segment_translation;
    }
    /** @var \Drupal\tmgmt_memory\SegmentStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment');
    $source = $storage->loadByLanguageAndData($source_language, $source_stripped_data);
    $target = $storage->loadByLanguageAndData($target_language, $target_stripped_data);
    if (!isset($source)) {
      $source = $this->addSegment($source_language, $source_stripped_data);
    }
    if (!isset($target)) {
      $target = $this->addSegment($target_language, $target_stripped_data);
    }
    $segment_translation = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment_translation')->create([
      'source' => $source->id(),
      'target' => $target->id(),
      'quality' => $quality,
      'state' => $state,
      'target_language' => $target_language,
    ]);
    $segment_translation->save();
    return $segment_translation;
  }

  /**
   * Get a SegmentTranslation using the source and target langcodes and data.
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
  public function getSegmentTranslation($source_language, $source_stripped_data, $target_language, $target_stripped_data = NULL) {
    /** @var \Drupal\tmgmt_memory\SegmentTranslationStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment_translation');
    return $storage->loadByLanguageAndData($source_language, $source_stripped_data, $target_language, $target_stripped_data);
  }

  /**
   * Get the SegmentTranslations using the source data and the langcodes.
   *
   * @param string $source_language
   *   The langcode of the source language.
   * @param string $source_stripped_data
   *   The source data without HTML tags.
   * @param string $target_language
   *   The langcode of the target language.
   *
   * @return \Drupal\tmgmt_memory\SegmentTranslationInterface[]|null
   *   An array of SegmentTranslation objects. NULL if no matching entity is
   *   found.
   */
  public function getSegmentTranslations($source_language, $source_stripped_data, $target_language) {
    /** @var \Drupal\tmgmt_memory\SegmentTranslationStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment_translation');
    return $storage->loadMultipleByLanguageAndData($source_language, $source_stripped_data, $target_language);
  }

  /**
   * Add a new translation of a usage.
   *
   * It will just create the new usage translation if it does not already exist,
   * otherwise will return the existing one.
   *
   * @param \Drupal\tmgmt_memory\UsageInterface $source
   *   The source usage.
   * @param \Drupal\tmgmt_memory\UsageInterface $target
   *   The target usage.
   * @param int $quality
   *   (Optional) The quality from 0 to 10.
   * @param bool $state
   *   (Optional) The state of the translation, TRUE if enabled (default),
   *   FALSE otherwise.
   *
   * @return \Drupal\tmgmt_memory\UsageTranslationInterface|NULL
   *   The new UsageTranslation or the existing one.
   */
  public function addUsageTranslation(UsageInterface $source, UsageInterface $target, $quality = NULL, $state = TRUE) {
    $this->addSegmentTranslation($source->getLangcode(), strip_tags($source->getData()), $target->getLangcode(), strip_tags($target->getData()), $quality);
    $usage_translation = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_usage_translation')->create([
      'source' => $source->id(),
      'target' => $target->id(),
      'quality' => $quality,
      'state' => $state,
      'target_language' => $target->getLangcode(),
    ]);
    $usage_translation->save();
    return $usage_translation;
  }

  /**
   * Get a UsageTranslation using the source and target langcodes and data.
   *
   * If there is more than one translation of the source segment to the target
   * language, this will return the translation with better quality.
   *
   * @param string $source_language
   *   The langcode of the source language.
   * @param string $source_data
   *   The source data with HTML tags.
   * @param string $target_language
   *   The langcode of the target language.
   * @param string $target_data
   *   (Optional) The source data with HTML tags.
   *
   * @return \Drupal\tmgmt_memory\UsageTranslationInterface|null
   *   A SegmentTranslation object. NULL if no matching entity is found.
   */
  public function getUsageTranslation($source_language, $source_data, $target_language, $target_data = NULL) {
    /** @var \Drupal\tmgmt_memory\UsageTranslationStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_usage_translation');
    return $storage->loadBestMatchByLanguageAndData($source_language, $source_data, $target_language, $target_data);
  }

  /**
   * Get the UsageTranslations using the source data and the langcodes.
   *
   * @param string $source_language
   *   The langcode of the source language.
   * @param string $source_data
   *   The source data with HTML tags.
   * @param string $target_language
   *   The langcode of the target language.
   *
   * @return \Drupal\tmgmt_memory\UsageTranslationInterface[]|null
   *   An array of UsageTranslation objects. NULL if no matching entity is
   *   found.
   */
  public function getUsageTranslations($source_language, $source_data, $target_language) {
    /** @var \Drupal\tmgmt_memory\UsageTranslationStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_usage_translation');
    return $storage->loadMultipleByLanguageAndData($source_language, $source_data, $target_language);
  }

  /**
   * Return a perfect match if exists.
   *
   * Is a perfect match if can find a perfect match for each segment that
   * contains the data_item.
   * And a perfect match in the segment will be if source of the translation is
   * identical to the segment in the data_item, including its tags.
   *
   * @param string $source_language
   *   The langcode of the source language.
   * @param string $target_language
   *   The langcode of the target language.
   * @param string $data_item
   *   The data item.
   *
   * @return string|NULL
   *   The translated data_item. NULL if there is no perfect match.
   */
  public function getPerfectMatchForDataItem($source_language, $target_language, $data_item) {
    /** @var \Drupal\tmgmt_memory\Segmenter $segmenter */
    $segmenter = \Drupal::service('tmgmt.segmenter');
    /** @var \Drupal\tmgmt_memory\UsageTranslationStorageInterface $usage_translation_storage */
    $usage_translation_storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_usage_translation');
    if ($data_item['#segmented_text']) {
      $segments = $segmenter->getSegmentsOfData($data_item['#segmented_text']);
      $translated_segments = [];
      foreach ($segments as $segment) {
        $entity = $usage_translation_storage->loadBestMatchByLanguageAndData($source_language, $segment['data'], $target_language);
        if (!isset($entity)) {
          return NULL;
        }
        $target_id = $entity->get('target')->target_id;
        /** @var \Drupal\tmgmt_memory\UsageInterface $target */
        $target = \Drupal::entityTypeManager()
          ->getStorage('tmgmt_memory_usage')
          ->load($target_id);
        $translated_segments[] = $target->getData();
      }
      $target_segmented_data_item = implode($translated_segments);
      return $segmenter->filterData($target_segmented_data_item);
    }
    return NULL;
  }

}
