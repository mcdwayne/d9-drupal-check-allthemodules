<?php

namespace Drupal\youtube_import\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Contains \Drupal\youtube_import\Controller\YoutubeController.
 */
class YoutubeController extends ControllerBase {

  /**
   * Importing youtube videos.
   */
  public function import() {
    // All this does is trigger the run from a url.
    youtube_import_videos();
    // Redirect back to youtube import main page.
    return $this->redirect('youtube_import.admin');
  }

}
