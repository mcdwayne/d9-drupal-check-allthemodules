<?php

namespace Drupal\pusher_integration\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * @file
 * Includes PusherDebugLogController.
 */
class PusherDebugLogController extends ControllerBase {

  /**
   * Watchdog logger.
   */
  public function log($msg) {

    \Drupal::logger('pusher_integration')->debug($msg);
  }

}
