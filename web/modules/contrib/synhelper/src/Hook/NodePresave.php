<?php

namespace Drupal\synhelper\Hook;

/**
 * @file
 * Contains \Drupal\synhelper\Hook\NodePresave.
 */

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller NodePresave.
 */
class NodePresave extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook($node) {
    // Set Node-title id empty.
    if (!$node->title->value) {
      $title = $node->getType() . " - " . format_date(REQUEST_TIME, 'long');
      $node->title->setValue($title);
    }
  }

}
