<?php

namespace Drupal\photoshelter\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Process synchronization of new additions in photoshelter.
 *
 * @QueueWorker(
 *   id = "photoshelter_syncnew_gallery",
 *   title = @Translation("Photoshelter sync gallery queue worker"),
 *   cron = {"time" = 30}
 * )
 */
class PhotoShelterSyncNewGallery extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    \Drupal::logger('photoshelter')->notice(t('synchronization of gallery') . ' ' . $data['gallery_id']);
    $service = \Drupal::service('photoshelter.photoshelter_service');
    $service->getGallery($data['gallery_id'], $data['time'], $data['update'], 'queue', $data['parentId']);
  }

}
