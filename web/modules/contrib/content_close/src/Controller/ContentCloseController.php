<?php

namespace Drupal\content_close\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * ContentCloseController class.
 */
class ContentCloseController extends ControllerBase {

  /**
   * Implements content method.
   */
  public function content($time = 0, $content_type = NULL) {
    $content_type = substr($content_type, 5, -5);
    return [
      '#theme' => 'content_close',
      '#expired_time' => $time,
      '#content_type' => $content_type,
    ];
  }

}
