<?php

/**
 * @file
 * Contains batch functions for photoshelter synchronization.
 */

use Drupal\taxonomy\Entity\Term;

/**
 * Batch function for gallery synchronization.
 *
 * @param string $gallery_id
 *   The gallery ID.
 * @param DateTime $time
 *   Date to compare with for update.
 * @param bool $update
 *   If update or full sync.
 * @param string $parentId
 *   Parent collection ID.
 * @param array $context
 *   The Batch context array.
 */
function photoshelter_sync_gallery($gallery_id, DateTime $time, $update, $parentId, &$context) {
  if (!isset($context['service'])) {
    $context['service'] = \Drupal::service('photoshelter.photoshelter_service');
  }

  $service = $context['service'];
  $service->getGallery($gallery_id, $time, $update, 'batch', $parentId);

  $context['results']['galleries'][] = $gallery_id;

  $message = t('Synchronization of gallery') . ' ' . $gallery_id;

  $context['message'] = $message;

}

/**
 * Batch function for collection synchronization.
 *
 * @param string $collection_id
 *   The collection ID.
 * @param DateTime $time
 *   Date to compare with for update.
 * @param bool $update
 *   If update or full sync.
 * @param string $parentId
 *   Parent collection ID.
 * @param array $context
 *   The Batch context array.
 */
function photoshelter_sync_collection($collection_id, DateTime $time, $update, $parentId, &$context) {
  if (!isset($context['service'])) {
    $context['service'] = \Drupal::service('photoshelter.photoshelter_service');
  }
  $service = $context['service'];
  $service->getCollection($collection_id, $time, $update, 'batch', $parentId);

  $context['results']['collections'][] = $collection_id;

  $message = t('Synchronization of collection') . ' ' . $collection_id;

  $context['message'] = $message;

}

/**
 * Gallery synchronization batch finish callback.
 *
 * @param bool $success
 *   If it succeed or not.
 * @param array $results
 *   Results item from context variable.
 * @param array $operations
 *   If success is false, array of not completed operations.
 */
function photoshelter_sync_finished($success, $results, $operations) {
  $messenger = \Drupal::messenger();
  $logger = \Drupal::logger('photoshelter');
  if ($success) {
    if (isset($results['collections'])) {
      $message1 = \Drupal::translation()->formatPlural(
        count($results['collections']),
        '1 Synchronized collection', '@count Synchronized collections.'
      );
      $logger->notice($message1);
      $messenger->addMessage($message1);
    }
    if (isset($results['galleries'])) {
      $message2 = \Drupal::translation()->formatPlural(
        count($results['galleries']),
        '1 Synchronized gallery', '@count Synchronized galleries.'
      );
      $logger->notice($message2);
      $messenger->addMessage($message2);
    }
  }
  else {
    $message = t('Finished with an error.');
    $logger->error($message);
    $messenger->addMessage($message);
  }
}

/**
 * Batch function for photo synchronization.
 *
 * @param array $image
 *   Image data array.
 * @param string $parentVisibility
 *   Parent visibility.
 * @param array $context
 *   The Batch context array.
 */
function photoshelter_sync_photo(array $image, $parentVisibility, &$context) {
  if (!isset($context['service'])) {
    $context['service'] = \Drupal::service('photoshelter.photoshelter_service');
  }
  if (!isset($context['parent_id'])) {
    $context['results']['parent_id'] = $image['Image']['gallery_id'];
  }

  $service = $context['service'];
  $service->getPhoto($image, $parentVisibility);

  $context['results']['images'][] = $image['Image']['file_name'];

  $message = t('Synchronization of photo') . ' ' . $image['Image']['file_name'];

  $context['message'] = $message;
}

/**
 * Photo synchronization batch finish callback.
 *
 * @param bool $success
 *   If it succeed or not.
 * @param array $results
 *   Results item from context variable.
 * @param array $operations
 *   If success is false, array of not completed operations.
 */
function photoshelter_sync_photo_finished($success, $results, $operations) {
  $messenger = \Drupal::messenger();
  $logger = \Drupal::logger('photoshelter');
  if ($success) {
    if (isset($results['images'])) {
      $message = \Drupal::translation()->formatPlural(
        count($results['images']),
        '1 Synchronized photo', '@count Synchronized photos.'
      );
      $logger->notice($message);
      $messenger->addMessage($message);
      if (count($results['images']) > 0) {
        $parent_gallery_tid = \Drupal::service('photoshelter.photoshelter_service')->getParentTerm($results['parent_id']);
        $term = Term::load($parent_gallery_tid);
        $term->set('field_ps_sync_complete', 1);
        $term->save();
      }
    }
  }
  else {
    $message = t('Finished with an error.');
    $logger->error($message);
    $messenger->addMessage($message);
  }
}

/**
 * Delete PhotoShelter media entities.
 *
 * @param array $mids
 *   Array of media entity ids.
 * @param $context
 *   The Batch context array.
 * @param array $operations
 *   If success is false, array of not completed operations.
 */
function photoshelter_delete_media($mids, &$context) {
  $medias = \Drupal::entityTypeManager()->getStorage('media')->loadMultiple($mids);
  \Drupal::entityTypeManager()->getStorage('media')->delete($medias);
  $message = t('Deleting media entities.');
  $context['message'] = $message;
}

/**
 * Delete PhotoShelter terms.
 *
 * @param array $tids
 *   Array of taxonomy term ids.
 * @param $context
 *   The Batch context array.
 * @param array $operations
 *   If success is false, array of not completed operations.
 */
function photoshelter_delete_terms($tids, &$context) {
  $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple($tids);
  \Drupal::entityTypeManager()->getStorage('taxonomy_term')->delete($terms);
  $message = t('Deleting taxonomy term entities.');
  $context['message'] = $message;
}

/**
 * Photo delete batch finish callback.
 *
 * @param bool $success
 *   If it succeed or not.
 * @param array $results
 *   Results item from context variable.
 * @param array $operations
 *   If success is false, array of not completed operations.
 */
function photoshelter_delete_finish($success, $results, $operations) {
  $messenger = \Drupal::messenger();
  $logger = \Drupal::logger('photoshelter');
  if ($success) {
    $message = t('All PhotoShelter data is deleted.');
    $logger->notice($message);
    $messenger->addMessage($message);
  }
  else {
    $message = t('Finished with an error.');
    $logger->error($message);
    $messenger->addMessage($message);
  }
}