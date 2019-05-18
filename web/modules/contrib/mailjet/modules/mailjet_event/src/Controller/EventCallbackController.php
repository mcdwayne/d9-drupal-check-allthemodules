<?php
/**
 * @file
 * Contains \Drupal\mailjet_event\Controller\EventCallbackController.
 */

namespace Drupal\mailjet_event\Controller;

use Drupal\Core\Controller\ControllerBase;

class EventCallbackController extends ControllerBase {

  public function callback() {
    $build = [];
    _mailjet_event_alter_callback();
    return $build;

  }
}