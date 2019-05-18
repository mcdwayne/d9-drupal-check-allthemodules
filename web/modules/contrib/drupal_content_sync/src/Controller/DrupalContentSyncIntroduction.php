<?php

namespace Drupal\drupal_content_sync\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class DrupalContentSyncIntroduction provides a static page describing how
 * Drupal Content Sync can be used.
 */
class DrupalContentSyncIntroduction extends ControllerBase {

  /**
   * @return array The content array to theme the introduction.
   */
  public function content() {
    return [
      '#theme' => 'drupal_content_sync_introduction',
    ];
  }

}
