<?php

namespace Drupal\bridtv\Batch;

/**
 * Holds helper methods for UI-based batch operations.
 */
abstract class BridBatchSync {

  static public function init() {
    $batch = [
      'title' => t('Synchronizing Brid.TV media...'),
      'operations' => [
        ['\Drupal\bridtv\Batch\BridBatchSync::start', []],
        ['\Drupal\bridtv\Batch\BridBatchSync::progress', []],
      ],
      'finished' => '\Drupal\bridtv\Batch\BridBatchSync::finished',
    ];
    batch_set($batch);
  }

  static public function start() {
    $sync = static::getSyncService();
    $sync->prepareFullSync();
  }

  static public function progress(&$context) {
    $sync = static::getSyncService();
    $limit = 1;
    if (!isset($context['sandbox']['total'])) {
      $context['sandbox']['total'] = $sync->getEntityResolver()->getEntityQuery()->count()->execute();
      $videos_list = $sync->getConsumer()->getDecodedVideosList(1, 1);
      if ($context['sandbox']['total'] == 0 && !$videos_list) {
        return;
      }
      if (isset($videos_list['Pagination']['count'])) {
        $context['sandbox']['total'] += $videos_list['Pagination']['count'];
      }
      $context['sandbox']['synced'] = 0;
    }

    for ($i = 0; $i !== $limit; $i++) {
      if (!$sync->processNextItem()) {
        $context['finished'] = 1.0;
        // We know that the queue seems to be empty,
        // thus the progress can stop here.
        return;
      };
      $context['sandbox']['synced'] += $sync::ITEMS_PER_QUEUE_ITEM;
    }

    $context['finished'] = $context['sandbox']['synced'] / $context['sandbox']['total'];
    // The queue operation cannot foresee the exact number of steps to process.
    // It's never 100% sure whether it's already finished here.
    // Thus, make sure the progress is not stopping here.
    if ($context['finished'] >= 1.0) {
      $context['finished'] = 0.99;
    }
  }

  static public function finished($success, $results, $operations) {
    if ($success) {
      \Drupal::messenger()->addMessage(t('All media items have been synchronized.'));
    }
    else {
      \Drupal::messenger()->addMessage(t('An error occurred. Please contact the site administrator.'));
    }
  }

  /**
   * Get the sync service.
   *
   * @return \Drupal\bridtv\BridSync
   *   The sync service.
   */
  static protected function getSyncService() {
    return \Drupal::service('bridtv.sync');
  }

}
