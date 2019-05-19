<?php

namespace Drupal\track_file_downloads\Controller;

use Drupal\system\FileDownloadController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tracking downloads file controller.
 */
class TrackingFileDownloadController extends FileDownloadController {

  /**
   * {@inheritdoc}
   */
  public function download(Request $request, $scheme = 'private') {
    // We can only track private files.
    if ($scheme !== 'private') {
      return parent::download($request, $scheme);
    }

    // The parent will throw an exception if the file isn't going to be
    // downloaded which will skip our tracking.
    $response = parent::download($request, $scheme);

    $target = $request->query->get('file');
    $uri = $scheme . '://' . $target;

    $this->trackDownload($uri);
    return $response;
  }

  /**
   * Tracks a download if appropriate against the relevant entity.
   *
   * @param string $uri
   *   The uri of the file we're downloading.
   */
  protected function trackDownload($uri) {
    if (!$files = $this->entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri])) {
      return;
    }
    if ($this->currentUser()->hasPermission('skip file tracking')) {
      return;
    }
    // Grab the file itself.
    $file = reset($files);

    // Find the related tracking entity.
    $tracker_storage = $this->entityTypeManager()->getStorage('file_tracker');
    $entities = $tracker_storage->loadByProperties(['file__target_id' => $file->id()]);
    /** @var \Drupal\track_file_downloads\Entity\FileTracker $tracker */
    if (!$tracker = reset($entities)) {
      return;
    }

    // Increment the view count and set the last downloaded date.
    $tracker->incrementDownloadCount()
      ->updateLastDownloadedDate()
      ->save();
  }

}
