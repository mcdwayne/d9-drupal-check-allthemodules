<?php

namespace Drupal\photoshelter\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Process synchronization of new additions in photoshelter.
 *
 * @QueueWorker(
 *   id = "photoshelter_syncnew_collection",
 *   title = @Translation("Photoshelter sync collection queue worker"),
 *   cron = {"time" = 30}
 * )
 */
class PhotoShelterSyncNewCollection extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    \Drupal::logger('photoshelter')->notice(t('synchronization of collection') . ' ' . $data['collection_id']);
    $service = \Drupal::service('photoshelter.photoshelter_service');
    $service->getCollection($data['collection_id'], $data['time'], $data['update'], 'queue', $data['parentId']);
  }

}
