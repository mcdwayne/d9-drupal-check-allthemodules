<?php

namespace Drupal\photoshelter\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Process synchronization of new additions in photoshelter.
 *
 * @QueueWorker(
 *   id = "photoshelter_syncnew_photo",
 *   title = @Translation("Photoshelter sync photo queue worker"),
 *   cron = {"time" = 30}
 * )
 */
class PhotoShelterSyncNewPhoto extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    \Drupal::logger('photoshelter')->notice(t('synchronization of photo') . ' ' . $data['image']['Image']['file_name']);
    $service = \Drupal::service('photoshelter.photoshelter_service');
    $service->getPhoto($data['image'], $data['$parentVisibility']);
  }

}
